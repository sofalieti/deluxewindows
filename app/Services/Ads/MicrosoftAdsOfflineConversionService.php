<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\PhoneClick;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uploads a confirmed phone call as an offline conversion to Microsoft Ads (Bing).
 *
 * @see https://learn.microsoft.com/en-us/advertising/campaign-management-service/applyofflineconversions
 */
final class MicrosoftAdsOfflineConversionService
{
    private const TOKEN_CACHE_KEY = 'microsoft-ads:access-token';

    /**
     * Microsoft rejects the whole request when a conversion time is older than this.
     */
    public const IMPORT_WINDOW_DAYS = 90;

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

    /**
     * Microsoft Advertising accounts opened with "Sign in with Google" authenticate
     * through Google instead of Entra ID, and must announce that on every call.
     */
    public function usesGoogleIdentity(): bool
    {
        return strtolower(trim((string) config('services.microsoft_ads.identity_provider'))) === 'google';
    }

    public function oauthTokenUrl(): string
    {
        return (string) config($this->usesGoogleIdentity()
            ? 'services.microsoft_ads.google_oauth_token_url'
            : 'services.microsoft_ads.oauth_token_url');
    }

    public function oauthAuthorizeUrl(): string
    {
        return (string) config($this->usesGoogleIdentity()
            ? 'services.microsoft_ads.google_oauth_authorize_url'
            : 'services.microsoft_ads.oauth_authorize_url');
    }

    public function oauthRedirectUri(): string
    {
        $configured = trim((string) config('services.microsoft_ads.oauth_redirect_uri'));
        if ($configured !== '') {
            return $configured;
        }

        return $this->usesGoogleIdentity()
            ? 'http://localhost'
            : 'https://login.microsoftonline.com/common/oauth2/nativeclient';
    }

    public function oauthScope(): string
    {
        return $this->usesGoogleIdentity()
            ? 'profile email'
            : 'https://ads.microsoft.com/msads.manage offline_access';
    }

    public function upload(PhoneClick $click): void
    {
        $msclkid = $click->resolvedMsclkid();
        if ($msclkid === null) {
            throw new RuntimeException('Phone click has no MSCLKID.');
        }

        if ($click->offlineConversionTime()->lt(now()->subDays(self::IMPORT_WINDOW_DAYS))) {
            throw new RuntimeException(
                'Call is older than the '.self::IMPORT_WINDOW_DAYS.' day Microsoft Ads import window.'
            );
        }

        $conversion = [
            'MicrosoftClickId' => $msclkid,
            'ConversionName' => trim((string) config('services.microsoft_ads.phone_conversion_name')),
            'ConversionTime' => $this->formatConversionTime($click),
            'ConversionCurrencyCode' => 'USD',
        ];

        // Enhanced conversions: lets Microsoft match the call even if the click id has expired.
        $hashedPhone = $this->hashedCallerPhone($click);
        if ($hashedPhone !== null) {
            $conversion['HashedPhoneNumber'] = $hashedPhone;
        }

        $base = (string) config('services.microsoft_ads.api_base_url');

        $headers = [
            'DeveloperToken' => trim((string) config('services.microsoft_ads.developer_token')),
            'CustomerId' => trim((string) config('services.microsoft_ads.customer_id')),
            'CustomerAccountId' => trim((string) config('services.microsoft_ads.account_id')),
        ];

        if ($this->usesGoogleIdentity()) {
            $headers['IdentityProvider'] = 'Google';
        }

        $response = $this->apply($base, $headers, $conversion, $this->accessToken());

        // Microsoft answers an expired access token with a fault, not a 401, so the
        // cached token has to be dropped explicitly before a single retry.
        if ($this->isAuthenticationFault($response)) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            $response = $this->apply($base, $headers, $conversion, $this->accessToken());
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'Microsoft Ads upload failed HTTP '.$response->status().': '.$this->errorMessage($response)
            );
        }

        $partialErrors = $response->json('PartialErrors');
        if (is_array($partialErrors) && $partialErrors !== []) {
            throw new RuntimeException('Microsoft Ads rejected the conversion: '.$this->errorMessage($response));
        }
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $conversion
     */
    private function apply(string $base, array $headers, array $conversion, string $accessToken): Response
    {
        return Http::withToken($accessToken)
            ->withHeaders($headers)
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($base.'/CampaignManagement/v13/OfflineConversions/Apply', [
                'OfflineConversions' => [$conversion],
            ]);
    }

    /**
     * SHA-256 hex of the caller number in E.164, as required for enhanced conversions.
     */
    private function hashedCallerPhone(PhoneClick $click): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $click->ringCentralClientPhone()) ?? '';

        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        if (strlen($digits) < 11 || strlen($digits) > 15) {
            return null;
        }

        return hash('sha256', '+'.$digits);
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

        $payload = [
            'client_id' => trim((string) config('services.microsoft_ads.client_id')),
            'client_secret' => trim((string) config('services.microsoft_ads.client_secret')),
            'refresh_token' => trim((string) config('services.microsoft_ads.refresh_token')),
            'grant_type' => 'refresh_token',
        ];

        // Google rejects a scope it did not issue the refresh token for.
        if (! $this->usesGoogleIdentity()) {
            $payload['scope'] = $this->oauthScope();
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($this->oauthTokenUrl(), $payload);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Microsoft Ads OAuth refresh failed HTTP '.$response->status().': '.$this->errorMessage($response)
            );
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('Microsoft Ads OAuth refresh returned no access token.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600));
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    /**
     * Microsoft returns its faults in several shapes (AdApiFaultDetail, ApiFaultDetail,
     * plain OAuth errors), so every known bucket is read and the raw body is kept as a
     * last resort. Anything less leaves the admin panel showing "unknown error".
     */
    private function errorMessage(Response $response): string
    {
        $payload = $response->json();
        $parts = [];

        foreach (['Errors', 'OperationErrors', 'BatchErrors', 'PartialErrors'] as $bucket) {
            foreach ((array) data_get($payload, $bucket, []) as $error) {
                $text = $this->describeError($error);
                if ($text !== '' && ! in_array($text, $parts, true)) {
                    $parts[] = $text;
                }
            }
        }

        foreach (['Message', 'error_description', 'error'] as $key) {
            $text = trim((string) (data_get($payload, $key) ?? ''));
            if ($text !== '' && ! in_array($text, $parts, true)) {
                $parts[] = $text;
            }
        }

        if ($parts === []) {
            $body = trim($response->body());
            $parts[] = $body !== '' ? Str::limit($body, 400) : 'empty response body';
        }

        $trackingId = trim((string) (data_get($payload, 'TrackingId') ?? ''));
        if ($trackingId !== '') {
            $parts[] = 'TrackingId '.$trackingId;
        }

        return implode('; ', $parts);
    }

    private function describeError(mixed $error): string
    {
        $code = trim((string) (data_get($error, 'ErrorCode') ?? ''));
        $number = data_get($error, 'Code');
        $message = trim((string) (data_get($error, 'Message') ?? ''));
        $details = trim((string) (data_get($error, 'Details') ?? data_get($error, 'Detail') ?? ''));

        if ($details !== '' && $details !== $message) {
            $message = trim($message.' ('.$details.')');
        }

        $label = implode(' ', array_filter([
            $code !== '' ? $code : null,
            is_numeric($number) ? '#'.$number : null,
        ]));

        if ($label === '') {
            return $message;
        }

        return $message !== '' ? $label.': '.$message : $label;
    }

    /**
     * Bing reports a stale access token as an HTTP 500 fault rather than a 401.
     */
    private function isAuthenticationFault(Response $response): bool
    {
        if ($response->successful()) {
            return false;
        }

        if ($response->status() === 401) {
            return true;
        }

        foreach ((array) data_get($response->json(), 'Errors', []) as $error) {
            $code = strtolower(trim((string) (data_get($error, 'ErrorCode') ?? '')));
            if (in_array($code, ['authenticationtokenexpired', 'invalidcredentials'], true)) {
                return true;
            }

            if (in_array((int) data_get($error, 'Code'), [105, 109], true)) {
                return true;
            }
        }

        return false;
    }
}
