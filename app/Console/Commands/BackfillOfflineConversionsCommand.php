<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendPhoneClickOfflineConversions;
use App\Models\PhoneClick;
use App\Services\Ads\GoogleAdsOfflineConversionService;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Re-sends confirmed calls that never reached the ad platforms.
 *
 * The upload normally happens the moment RingCentral confirms a call, but it is
 * skipped whenever credentials are missing and it is dropped on transient API
 * failures. Without a sweep those calls would stay unreported forever.
 */
class BackfillOfflineConversionsCommand extends Command
{
    protected $signature = 'ads:backfill-offline-conversions
        {--days=30 : How far back to look, capped at the 90 day import window}
        {--limit=200 : Maximum number of phone clicks to queue in one run}
        {--force : Re-send even if the conversion was already uploaded}
        {--dry-run : List what would be queued without dispatching anything}';

    protected $description = 'Queue offline conversion uploads for confirmed phone clicks that were never sent';

    public function handle(
        GoogleAdsOfflineConversionService $google,
        MicrosoftAdsOfflineConversionService $microsoft,
    ): int {
        if (! Schema::hasColumn('phone_clicks', 'google_ads_conversion_sent_at')) {
            $this->warn('Run php artisan migrate — offline conversion columns are missing.');

            return self::SUCCESS;
        }

        $platforms = array_filter([
            'google' => $google->isConfigured(),
            'bing' => $microsoft->isConfigured(),
        ]);

        if ($platforms === []) {
            $this->warn('Neither Google Ads nor Microsoft Ads credentials are configured; nothing to send.');

            return self::SUCCESS;
        }

        $days = min(
            max(1, (int) $this->option('days')),
            MicrosoftAdsOfflineConversionService::IMPORT_WINDOW_DAYS
        );
        $limit = max(1, (int) $this->option('limit'));
        $force = (bool) $this->option('force');

        $clicks = $this->candidates(array_keys($platforms), $days, $force)
            ->limit($limit)
            ->get();

        if ($clicks->isEmpty()) {
            $this->info('No confirmed calls are waiting to be uploaded.');

            return self::SUCCESS;
        }

        foreach ($clicks as $click) {
            $this->line(sprintf(
                '#%d  %s  gclid:%s  msclkid:%s',
                $click->id,
                $click->offlineConversionTime()->toDateTimeString(),
                $click->resolvedGclid() ? 'yes' : 'no',
                $click->resolvedMsclkid() ? 'yes' : 'no',
            ));

            if (! $this->option('dry-run')) {
                SendPhoneClickOfflineConversions::dispatch($click->id, $force);
            }
        }

        $this->info(sprintf(
            '%s %d phone click(s) for %s.',
            $this->option('dry-run') ? 'Would queue' : 'Queued',
            $clicks->count(),
            implode(' + ', array_keys($platforms)),
        ));

        return self::SUCCESS;
    }

    /**
     * Only rows that a configured platform can actually accept, so repeated runs
     * do not keep re-queueing calls that will never match.
     *
     * @param  list<string>  $platforms
     */
    private function candidates(array $platforms, int $days, bool $force): Builder
    {
        return PhoneClick::query()
            ->notSpam()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->where('created_at', '>=', now()->subDays($days))
            ->where(function (Builder $query) use ($platforms, $force): void {
                foreach ($platforms as $platform) {
                    $query->orWhere(function (Builder $inner) use ($platform, $force): void {
                        [$sentAt, $clickIds] = $platform === 'google'
                            ? ['google_ads_conversion_sent_at', ['gclid', 'first_gclid']]
                            : ['bing_ads_conversion_sent_at', ['msclkid', 'first_msclkid']];

                        if (! $force) {
                            $inner->whereNull($sentAt);
                        }

                        $inner->where(function (Builder $ids) use ($clickIds): void {
                            foreach ($clickIds as $column) {
                                $ids->orWhere(function (Builder $present) use ($column): void {
                                    $present->whereNotNull($column)->where($column, '!=', '');
                                });
                            }
                        });
                    });
                }
            })
            ->orderByDesc('id');
    }
}
