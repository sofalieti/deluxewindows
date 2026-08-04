<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\PhoneClick;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Uploads a confirmed phone call as an offline click conversion to Google Ads.
 *
 * @see https://developers.google.com/google-ads/api/docs/conversions/upload-clicks
 */
final class GoogleAdsOfflineConversionService
{
    private const TOKEN_CACHE_KEY = 'google-ads:access-token';

    public function isConfigured(): bool
    {
        foreach (['developer_token', 'client_id', 'client_secret', 'refresh_token', 'customer_id', 'phone_conversion_action'] as $key) {
            if (trim((string) config('services.google_ads.'.$key)) === '') {
                return false;
            }
        }

        return true;
    }

    public function supports(PhoneClick $click): bool
    {
        return $click->resolvedGclid() !== null;
    }

    public function upload(PhoneClick $click): void
    {
        $gclid = $click->resolvedGclid();
        if ($gclid === null) {
            throw new RuntimeException('Phone click has no GCLID.');
        }

        $customerId = $this->customerId();
        $version = trim((string) config('services.google_ads.api_version', 'v18'));
        $base = (string) config('services.google_ads.api_base_url');

        $headers = [
            'developer-token' => trim((string) config('services.google_ads.developer_token')),
        ];

        $loginCustomerId = $this->digitsOnly((string) config('services.google_ads.login_customer_id'));
        if ($loginCustomerId !== '') {
            $headers['login-customer-id'] = $loginCustomerId;
        }

        $response = Http::withToken($this->accessToken())
            ->withHeaders($headers)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($base.'/'.$version.'/customers/'.$customerId.':uploadClickConversions', [
                'conversions' => [[
                    'gclid' => $gclid,
                    'conversionAction' => trim((string) config('services.google_ads.phone_conversion_action')),
                    'conversionDateTime' => $this->formatConversionTime($click),
                    'orderId' => $click->offlineConversionOrderId(),
                ]],
                'partialFailure' => true,
                'validateOnly' => false,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Ads upload failed HTTP '.$response->status().': '.$this->errorMessage($response->json())
            );
        }

        $partialFailure = $response->json('partialFailureError');
        if (is_array($partialFailure)) {
            throw new RuntimeException('Google Ads rejected the conversion: '.$this->errorMessage($response->json()));
        }
    }

    /**
     * Google expects "yyyy-mm-dd hh:mm:ss+|-hh:mm" in the account time zone.
     */
    private function formatConversionTime(PhoneClick $click): string
    {
        return $click->offlineConversionTime()
            ->setTimezone((string) config('app.timezone', 'America/Los_Angeles'))
            ->format('Y-m-d H:i:sP');
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post((string) config('services.google_ads.oauth_token_url'), [
                'client_id' => trim((string) config('services.google_ads.client_id')),
                'client_secret' => trim((string) config('services.google_ads.client_secret')),
                'refresh_token' => trim((string) config('services.google_ads.refresh_token')),
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Google Ads OAuth refresh failed HTTP '.$response->status().'.');
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('Google Ads OAuth refresh returned no access token.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600));
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    private function customerId(): string
    {
        $id = $this->digitsOnly((string) config('services.google_ads.customer_id'));
        if ($id === '') {
            throw new RuntimeException('Google Ads customer id is not configured.');
        }

        return $id;
    }

    private function digitsOnly(string $value): string
    {
        return (string) preg_replace('/\D/', '', $value);
    }

    private function errorMessage(mixed $payload): string
    {
        $message = data_get($payload, 'error.message')
            ?? data_get($payload, 'partialFailureError.message')
            ?? '';

        $message = trim((string) $message);

        return $message !== '' ? $message : 'unknown error';
    }
}
