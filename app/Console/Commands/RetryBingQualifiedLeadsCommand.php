<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLeadQualifiedToMicrosoftAds;
use App\Models\Lead;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;

/**
 * Re-queues qualified leads whose Microsoft upload failed or never ran. The job re-sends
 * the frozen name / click id / time, so a retry cannot create a second conversion.
 */
class RetryBingQualifiedLeadsCommand extends Command
{
    protected $signature = 'ads:retry-bing-qualified
        {--lead= : Only this lead id}
        {--max-attempts=5 : Give up after this many attempts}
        {--sync : Run in this process instead of the queue}';

    protected $description = 'Retry pending or failed Microsoft Ads "Qualified Lead - 3+ Windows" uploads.';

    public function handle(): int
    {
        $query = Lead::query()
            ->whereIn('bing_qualified_status', [Lead::BING_QUALIFIED_PENDING, Lead::BING_QUALIFIED_FAILED])
            ->whereNull('bing_qualified_sent_at')
            ->where('bing_qualified_attempts', '<', max(1, (int) $this->option('max-attempts')))
            ->where('bing_qualified_at', '>=', now()->subDays(MicrosoftAdsOfflineConversionService::IMPORT_WINDOW_DAYS));

        if ($this->option('lead')) {
            $query->whereKey((int) $this->option('lead'));
        }

        $count = 0;
        foreach ($query->orderBy('id')->pluck('id') as $id) {
            $this->option('sync')
                ? SendLeadQualifiedToMicrosoftAds::dispatchSync((int) $id)
                : SendLeadQualifiedToMicrosoftAds::dispatch((int) $id);
            $count++;
        }

        $this->info('Queued '.$count.' qualified lead upload(s).');

        return self::SUCCESS;
    }
}
