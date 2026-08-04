<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PhoneClick;
use App\Services\Ads\GoogleAdsOfflineConversionService;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Uploads offline conversions once a phone click is confirmed by a RingCentral call.
 *
 * Browser tags cannot fire here: the call is confirmed minutes after the visitor left,
 * so the ad platforms are only reachable through their offline conversion APIs.
 */
class SendPhoneClickOfflineConversions implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $phoneClickId,
        public readonly bool $force = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(
        GoogleAdsOfflineConversionService $google,
        MicrosoftAdsOfflineConversionService $microsoft,
    ): void {
        if (! Schema::hasColumn('phone_clicks', 'google_ads_conversion_sent_at')) {
            return;
        }

        Cache::lock('ads:offline-conversion:'.$this->phoneClickId, 90)->get(
            function () use ($google, $microsoft): void {
                $click = PhoneClick::query()->find($this->phoneClickId);
                if (! $click || $click->isSpam()) {
                    return;
                }

                if ($click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
                    return;
                }

                $this->send(
                    $click,
                    $google,
                    'google_ads_conversion_sent_at',
                    'google_ads_conversion_error',
                    'Google Ads'
                );

                $this->send(
                    $click,
                    $microsoft,
                    'bing_ads_conversion_sent_at',
                    'bing_ads_conversion_error',
                    'Microsoft Ads'
                );
            }
        );
    }

    /**
     * @param  GoogleAdsOfflineConversionService|MicrosoftAdsOfflineConversionService  $service
     */
    private function send(
        PhoneClick $click,
        object $service,
        string $sentAtColumn,
        string $errorColumn,
        string $platform,
    ): void {
        if (! $this->force && $click->{$sentAtColumn} !== null) {
            return;
        }

        if (! $service->supports($click)) {
            return;
        }

        if (! $service->isConfigured()) {
            Log::info($platform.' offline conversion skipped — credentials are not configured.', [
                'phone_click_id' => $click->id,
            ]);

            return;
        }

        try {
            $service->upload($click);

            $click->forceFill([
                $sentAtColumn => now(),
                $errorColumn => null,
            ])->save();
        } catch (Throwable $exception) {
            Log::warning($platform.' offline conversion upload failed', [
                'phone_click_id' => $click->id,
                'error' => $exception->getMessage(),
            ]);

            $click->forceFill([
                $errorColumn => Str::limit($exception->getMessage(), 1000, ''),
            ])->save();
        }
    }
}
