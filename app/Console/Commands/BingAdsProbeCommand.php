<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PhoneClick;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Replays one phone click against Microsoft Ads and prints the payload + outcome.
 *
 * Use this on the server when ads:bing-status shows Failed / InternalError and the
 * card alone is not enough to see what was actually sent.
 */
class BingAdsProbeCommand extends Command
{
    protected $signature = 'ads:bing-probe
        {click : Phone click id}
        {--dry-run : Show the payload without calling Microsoft}';

    protected $description = 'Upload (or dry-run) one confirmed phone click to Microsoft Ads and print the result';

    public function handle(MicrosoftAdsOfflineConversionService $microsoft): int
    {
        $click = PhoneClick::query()->find((int) $this->argument('click'));
        if ($click === null) {
            $this->components->error('Phone click not found.');

            return self::FAILURE;
        }

        $this->components->info('Phone click #'.$click->id);
        $this->components->twoColumnDetail('RingCentral', (string) $click->ringcentral_status);
        $this->components->twoColumnDetail('MSCLKID', (string) ($click->resolvedMsclkid() ?? '<missing>'));
        $this->components->twoColumnDetail(
            'Conversion time',
            $click->offlineConversionTime()->utc()->format('Y-m-d H:i:s').' UTC'
        );
        $this->components->twoColumnDetail('Caller', (string) ($click->ringCentralClientPhone() ?? '-'));
        $this->components->twoColumnDetail(
            'Bing sent at',
            $click->bing_ads_conversion_sent_at?->toDateTimeString() ?? 'never'
        );
        $this->components->twoColumnDetail(
            'Last Bing error',
            $click->bing_ads_conversion_error ?: '-'
        );

        if (! $microsoft->supports($click)) {
            $this->components->error('No MSCLKID on this click — nothing to upload.');

            return self::FAILURE;
        }

        if ($click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
            $this->components->error('RingCentral has not confirmed this click yet.');

            return self::FAILURE;
        }

        try {
            $payload = $microsoft->conversionPayload($click);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Payload');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run — Microsoft was not called.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Uploading…');

        try {
            $microsoft->upload($click);
        } catch (Throwable $exception) {
            $click->forceFill([
                'bing_ads_conversion_error' => \Illuminate\Support\Str::limit($exception->getMessage(), 1000, ''),
            ])->save();

            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $click->forceFill([
            'bing_ads_conversion_sent_at' => now(),
            'bing_ads_conversion_error' => null,
        ])->save();

        $this->components->info('Upload accepted. Marked phone click #'.$click->id.' as sent.');

        return self::SUCCESS;
    }
}
