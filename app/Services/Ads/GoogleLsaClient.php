<?php

declare(strict_types=1);

namespace App\Services\Ads;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Reads Local Services Ads account and lead reports.
 *
 * @see https://developers.google.com/local-services-ads/guides/reporting
 */
final class GoogleLsaClient
{
    private const TOKEN_CACHE_KEY = 'google-lsa:access-token';

    public function hasClient(): bool
    {
        return $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    public function hasRefreshToken(): bool
    {
        return $this->refreshToken() !== '';
    }

    /**
     * 10-digit manager account. Falls back to the Google Ads MCC, then the Ads customer.
     */
    public function managerCustomerId(): string
    {
        foreach ([
            config('services.google_lsa.manager_customer_id'),
            config('services.google_ads.login_customer_id'),
            config('services.google_ads.customer_id'),
        ] as $value) {
            $digits = $this->digitsOnly((string) $value);
            if ($digits !== '') {
                return $digits;
            }
        }

        return '';
    }

    public function authorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/adwords',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $this->oauthState(),
        ]);
    }

    public function oauthState(): string
    {
        return hash_hmac('sha256', 'google-lsa', (string) config('app.key'));
    }

    public function isOAuthReturn(string $code, string $state): bool
    {
        $expected = $this->oauthState();

        return $code !== ''
            && strlen($state) === strlen($expected)
            && hash_equals($expected, $state);
    }

    public function envPath(): string
    {
        $path = trim((string) config('services.google_lsa.env_path'));

        return $path !== '' ? $path : base_path('.env');
    }

    public function exchangeAuthorizationCode(string $code): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($this->tokenUrl(), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'code' => $code,
                'redirect_uri' => $this->redirectUri(),
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Token exchange failed HTTP '.$response->status().': '.$this->errorMessage($response));
        }

        $refreshToken = trim((string) $response->json('refresh_token'));
        if ($refreshToken === '') {
            throw new RuntimeException('Google returned no refresh token. Revoke the app under the Google account and approve it again.');
        }

        return $refreshToken;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function accountReports(): array
    {
        $response = $this->report('accountReports:search');
        $rows = $response->json('accountReports');

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function detailedLeadReports(int $pageSize = 20): array
    {
        $response = $this->report('detailedLeadReports:search', [
            'pageSize' => max(1, min(100, $pageSize)),
        ]);
        $rows = $response->json('detailedLeadReports');

        return is_array($rows) ? array_values($rows) : [];
    }

    /**
     * @param  array<string, string>  $pairs
     */
    public static function upsertEnvFile(string $path, array $pairs): void
    {
        $lines = is_file($path) ? file($path, FILE_IGNORE_NEW_LINES) : [];
        if ($lines === false) {
            throw new RuntimeException('Could not read '.$path);
        }

        foreach ($pairs as $key => $value) {
            $key = trim($key);
            $value = trim($value);
            if ($key === '' || $value === '') {
                continue;
            }

            $found = false;
            foreach ($lines as $index => $line) {
                if (str_starts_with($line, $key.'=')) {
                    $lines[$index] = $key.'='.$value;
                    $found = true;
                }
            }

            if (! $found) {
                $lines[] = $key.'='.$value;
            }
        }

        if (file_put_contents($path, implode("\n", $lines)."\n") === false) {
            throw new RuntimeException('Could not write '.$path);
        }
    }

    /**
     * @param  array<string, int|string>  $extra
     */
    private function report(string $method, array $extra = []): Response
    {
        $managerId = $this->managerCustomerId();
        if ($managerId === '') {
            throw new RuntimeException('Set GOOGLE_LSA_MANAGER_CUSTOMER_ID to the 10-digit Google Ads manager id, without dashes.');
        }

        $end = now()->timezone((string) config('app.timezone', 'America/Los_Angeles'));
        $start = $end->copy()->subDays(6);

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->get(rtrim((string) config('services.google_lsa.api_base_url'), '/').'/v1/'.$method, array_merge([
                'query' => 'manager_customer_id:'.$managerId,
                'startDate.year' => $start->year,
                'startDate.month' => $start->month,
                'startDate.day' => $start->day,
                'endDate.year' => $end->year,
                'endDate.month' => $end->month,
                'endDate.day' => $end->day,
            ], $extra));

        if (! $response->successful()) {
            throw new RuntimeException($method.' failed HTTP '.$response->status().': '.$this->errorMessage($response));
        }

        return $response;
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if (! $this->hasRefreshToken()) {
            throw new RuntimeException('GOOGLE_LSA_REFRESH_TOKEN is empty. Run php artisan ads:lsa-oauth and approve the Google account that owns Local Services.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($this->tokenUrl(), [
                'client_id' => $this->clientId(),
                'client_secret' => $this->clientSecret(),
                'refresh_token' => $this->refreshToken(),
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OAuth refresh failed HTTP '.$response->status().': '.$this->errorMessage($response));
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('OAuth refresh returned no access token.');
        }

        $expiresIn = max(60, (int) ($response->json('expires_in') ?? 3600));
        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addSeconds($expiresIn - 60));

        return $token;
    }

    private function errorMessage(Response $response): string
    {
        $message = $response->json('error.message')
            ?? $response->json('error_description')
            ?? $response->json('error');

        if (is_string($message) && $message !== '') {
            return $message;
        }

        return 'Google returned '.$response->status();
    }

    private function clientId(): string
    {
        return trim((string) config('services.google_lsa.client_id'));
    }

    private function clientSecret(): string
    {
        return trim((string) config('services.google_lsa.client_secret'));
    }

    private function refreshToken(): string
    {
        return trim((string) config('services.google_lsa.refresh_token'));
    }

    private function redirectUri(): string
    {
        return trim((string) config('services.google_lsa.oauth_redirect_uri', 'http://localhost'));
    }

    private function tokenUrl(): string
    {
        return trim((string) config('services.google_lsa.oauth_token_url'));
    }

    private function digitsOnly(string $value): string
    {
        return (string) preg_replace('/\D/', '', $value);
    }
}
