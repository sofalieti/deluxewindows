<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\PhoneClick;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Uploads a confirmed phone call as an offline conversion to Microsoft Ads (Bing).
 *
 * @see https://learn.microsoft.com/en-us/advertising/campaign-management-service/applyofflineconversions
 */
final class MicrosoftAdsOfflineConversionService
{
    private const TOKEN_CACHE_KEY = 'microsoft-ads:access-token';

    public function isConfigured(): bool
    {
        $keys = [
            'developer_token',
            'client_id',
            'client_secret',
            'refresh_token',
            'customer_id',
            'account_id',
            'phone_conversion_name',
        ];

        foreach ($keys as $key) {
            if (trim((string) config('services.microsoft_ads.'.$key)) === '') {
                return false;
            }
        }

        return true;
    }

    public function supports(PhoneClick $click): bool
    {
        return $click->resolvedMsclkid() !== null;
    }

    public function upload(PhoneClick $click): void
    {
        $msclkid = $click->resolvedMsclkid();
        if ($msclkid === null) {
            throw new RuntimeException('Phone click has no MSCLKID.');
        }

        $base = (string) config('services.microsoft_ads.api_base_url');

        $response = Http::withToken($this->accessToken())
            ->withHeaders([
                'DeveloperToken' => trim((string) config('services.microsoft_ads.developer_token')),
                'CustomerId' => trim((string) config('services.microsoft_ads.customer_id')),
                'CustomerAccountId' => trim((string) config('services.microsoft_ads.account_id')),
            ])
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($base.'/CampaignManagement/v13/OfflineConversions/Apply', [
                'OfflineConversions' => [[
                    'MicrosoftClickId' => $msclkid,
                    'ConversionName' => trim((string) config('services.microsoft_ads.phone_conversion_name')),
                    'ConversionTime' => $this->formatConversionTime($click),
                    'ConversionCurrencyCode' => 'USD',
                ]],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Microsoft Ads upload failed HTTP '.$response->status().': '.$this->errorMessage($response->json())
            );
        }

        $partialErrors = $response->json('PartialErrors');
        if (is_array($partialErrors) && $partialErrors !== []) {
            throw new RuntimeException('Microsoft Ads rejected the conversion: '.$this->partialErrorMessage($partialErrors));
        }
    }

    /**
     * Microsoft expects UTC in ISO 8601.
     */
    private function formatConversionTime(PhoneClick $click): string
    {
        return $click->offlineConversionTime()->utc()->format('Y-m-d\TH:i:s\Z');
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
            ->post((string) config('services.microsoft_ads.oauth_token_url'), [
                'client_id' => trim((string) config('services.microsoft_ads.client_id')),
                'client_secret' => trim((string) config('services.microsoft_ads.client_secret')),
                'refresh_token' => trim((string) config('services.microsoft_ads.refresh_token')),
                'grant_type' => 'refresh_token',
                'scope' => 'https://ads.microsoft.com/msads.manage offline_access',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Microsoft Ads OAuth refresh failed HTTP '.$response->status().'.');
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('Microsoft Ads OAuth refresh returned no access token.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600));
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    private function errorMessage(mixed $payload): string
    {
        $message = data_get($payload, 'Message')
            ?? data_get($payload, 'error_description')
            ?? '';

        $message = trim((string) $message);

        return $message !== '' ? $message : 'unknown error';
    }

    /**
     * @param  array<int, mixed>  $partialErrors
     */
    private function partialErrorMessage(array $partialErrors): string
    {
        $messages = [];
        foreach ($partialErrors as $error) {
            $text = trim((string) (data_get($error, 'Message') ?? data_get($error, 'ErrorCode') ?? ''));
            if ($text !== '') {
                $messages[] = $text;
            }
        }

        return $messages !== [] ? implode('; ', $messages) : 'unknown error';
    }
}
