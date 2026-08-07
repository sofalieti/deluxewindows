<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\PhoneClick;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Builds the official Google Ads GCLID offline-conversion spreadsheet and
 * creates it in a Drive folder (service account or OAuth).
 *
 * @see https://support.google.com/google-ads/answer/7014069
 */
final class GoogleAdsOfflineSheetExporter
{
    private const TOKEN_CACHE_KEY = 'google-drive:access-token';

    private const SHEET_MIME = 'application/vnd.google-apps.spreadsheet';

    private const HEADER_ROW = [
        'Google Click ID',
        'Conversion Name',
        'Conversion Time',
        'Conversion Value',
        'Conversion Currency',
        'Ad User Data',
        'Ad Personalization',
    ];

    public function isConfigured(): bool
    {
        return $this->configurationError() === null;
    }

    /**
     * Human-readable reason the export cannot run, or null when ready.
     */
    public function configurationError(): ?string
    {
        if (trim((string) config('services.google_drive.folder_id')) === '') {
            return 'Set GOOGLE_DRIVE_FOLDER_ID in .env.';
        }

        if (trim((string) config('services.google_ads.phone_conversion_name')) === '') {
            return 'Set GOOGLE_ADS_PHONE_CONVERSION_NAME in .env (exact Ads conversion action name).';
        }

        $auth = $this->authMode();

        if ($auth === 'service_account') {
            if ($this->serviceAccountCredentials() === null) {
                return 'Set GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON to a service-account JSON file path (or inline JSON), then share the Drive folder with that SA email as Editor.';
            }

            return null;
        }

        if ($auth === 'oauth') {
            foreach (['client_id' => 'GOOGLE_DRIVE_CLIENT_ID (or GOOGLE_ADS_CLIENT_ID)', 'client_secret' => 'GOOGLE_DRIVE_CLIENT_SECRET (or GOOGLE_ADS_CLIENT_SECRET)', 'refresh_token' => 'GOOGLE_DRIVE_REFRESH_TOKEN'] as $key => $env) {
                if (trim((string) config('services.google_drive.'.$key)) === '') {
                    return 'OAuth mode: set '.$env.' (scopes: drive.file + spreadsheets).';
                }
            }

            return null;
        }

        return 'GOOGLE_DRIVE_AUTH must be service_account or oauth (got: '.$auth.').';
    }

    /**
     * @return array{
     *     count: int,
     *     spreadsheet_id: string|null,
     *     spreadsheet_url: string|null,
     *     title: string,
     *     dry_run: bool,
     *     rows: list<list<string>>,
     *     click_ids: list<int>
     * }
     */
    public function export(?string $date = null, bool $allPending = false, bool $dryRun = false): array
    {
        $timezone = $this->timezone();
        $titleDate = $date !== null && $date !== ''
            ? CarbonImmutable::parse($date, $timezone)->toDateString()
            : CarbonImmutable::now($timezone)->subDay()->toDateString();

        $clicks = $this->eligibleClicks($allPending ? null : $titleDate)->get();
        $rows = $this->buildDataRows($clicks);
        $grid = $this->buildGrid($rows);
        $title = 'Google Ads Offline Conversions '.$titleDate;
        $clickIds = $clicks->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        if ($dryRun || $clicks->isEmpty()) {
            return [
                'count' => count($rows),
                'spreadsheet_id' => null,
                'spreadsheet_url' => null,
                'title' => $title,
                'dry_run' => true,
                'rows' => $rows,
                'click_ids' => $clickIds,
            ];
        }

        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Google Drive sheet export is not configured. Set GOOGLE_DRIVE_FOLDER_ID, conversion name, and service account or OAuth credentials.'
            );
        }

        $spreadsheetId = $this->createSpreadsheet($title);
        $this->writeValues($spreadsheetId, $grid);
        $url = 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit';

        $now = now();
        PhoneClick::query()
            ->whereIn('id', $clickIds)
            ->update([
                'google_ads_sheet_exported_at' => $now,
                'google_ads_sheet_url' => $url,
            ]);

        return [
            'count' => count($rows),
            'spreadsheet_id' => $spreadsheetId,
            'spreadsheet_url' => $url,
            'title' => $title,
            'dry_run' => false,
            'rows' => $rows,
            'click_ids' => $clickIds,
        ];
    }

    /**
     * @return Builder<PhoneClick>
     */
    public function eligibleClicks(?string $dateYmd): Builder
    {
        $timezone = $this->timezone();

        $query = PhoneClick::query()
            ->where('ringcentral_status', PhoneClick::RINGCENTRAL_FOUND)
            ->where(function (Builder $q): void {
                $q->where('is_spam', false)->orWhereNull('is_spam');
            })
            ->where(function (Builder $q): void {
                $q->where(function (Builder $inner): void {
                    $inner->whereNotNull('gclid')->where('gclid', '!=', '');
                })->orWhere(function (Builder $inner): void {
                    $inner->whereNotNull('first_gclid')->where('first_gclid', '!=', '');
                });
            })
            ->whereNull('google_ads_sheet_exported_at')
            ->orderBy('id');

        if ($dateYmd !== null && $dateYmd !== '') {
            $dayStart = CarbonImmutable::parse($dateYmd, $timezone)->startOfDay();
            $dayEnd = $dayStart->endOfDay();

            // Conversion time = ringcentral_call_started_at, else created_at (same as offlineConversionTime()).
            // Stored in app timezone (America/Los_Angeles), matching the export calendar day.
            $query->whereRaw(
                'COALESCE(ringcentral_call_started_at, created_at) >= ? AND COALESCE(ringcentral_call_started_at, created_at) <= ?',
                [$dayStart->format('Y-m-d H:i:s'), $dayEnd->format('Y-m-d H:i:s')]
            );
        }

        return $query;
    }

    /**
     * @param  Collection<int, PhoneClick>  $clicks
     * @return list<list<string>>
     */
    public function buildDataRows(Collection $clicks): array
    {
        $conversionName = trim((string) config('services.google_ads.phone_conversion_name'));
        $timezone = $this->timezone();
        $rows = [];

        foreach ($clicks as $click) {
            $gclid = $click->resolvedGclid();
            if ($gclid === null) {
                continue;
            }

            $rows[] = [
                $gclid,
                $conversionName,
                $this->formatSheetConversionTime($click, $timezone),
                '1',
                'USD',
                '',
                '',
            ];
        }

        return $rows;
    }

    /**
     * @param  list<list<string>>  $dataRows
     * @return list<list<string>>
     */
    public function buildGrid(array $dataRows): array
    {
        return array_merge(
            [['Parameters:TimeZone='.$this->timezone()]],
            [self::HEADER_ROW],
            $dataRows,
        );
    }

    public function formatSheetConversionTime(PhoneClick $click, ?string $timezone = null): string
    {
        $tz = $timezone ?? $this->timezone();

        // Official template: yyyy-MM-dd HH:mm:ssxxxx (offset without colon), e.g. 2026-08-05 13:54:40-0700
        return $click->offlineConversionTime()
            ->setTimezone($tz)
            ->format('Y-m-d H:i:sO');
    }

    private function createSpreadsheet(string $title): string
    {
        $folderId = trim((string) config('services.google_drive.folder_id'));
        $base = (string) config('services.google_drive.drive_api_base_url');

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($base.'/files', [
                'name' => $title,
                'mimeType' => self::SHEET_MIME,
                'parents' => [$folderId],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Drive create failed HTTP '.$response->status().': '.$response->body()
            );
        }

        $id = trim((string) $response->json('id'));
        if ($id === '') {
            throw new RuntimeException('Drive create returned no spreadsheet id.');
        }

        return $id;
    }

    /**
     * @param  list<list<string>>  $grid
     */
    private function writeValues(string $spreadsheetId, array $grid): void
    {
        $base = (string) config('services.google_drive.sheets_api_base_url');
        $endRow = $this->columnLetter(count(self::HEADER_ROW)).(count($grid));

        $response = Http::withToken($this->accessToken())
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->put($base.'/spreadsheets/'.$spreadsheetId.'/values/Sheet1!A1:'.$endRow.'?valueInputOption=RAW', [
                'range' => 'Sheet1!A1:'.$endRow,
                'majorDimension' => 'ROWS',
                'values' => $grid,
            ]);

        if (! $response->successful()) {
            // New spreadsheets are usually named "Sheet1"; retry first sheet via A1 without name.
            $fallback = Http::withToken($this->accessToken())
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30)
                ->put($base.'/spreadsheets/'.$spreadsheetId.'/values/A1:'.$endRow.'?valueInputOption=RAW', [
                    'range' => 'A1:'.$endRow,
                    'majorDimension' => 'ROWS',
                    'values' => $grid,
                ]);

            if (! $fallback->successful()) {
                throw new RuntimeException(
                    'Sheets write failed HTTP '.$response->status().': '.$response->body()
                );
            }
        }
    }

    private function accessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->authMode() === 'oauth'
            ? $this->fetchOAuthAccessToken()
            : $this->fetchServiceAccountAccessToken();

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addMinutes(50));

        return $token;
    }

    private function fetchOAuthAccessToken(): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->post((string) config('services.google_drive.oauth_token_url'), [
                'grant_type' => 'refresh_token',
                'client_id' => trim((string) config('services.google_drive.client_id')),
                'client_secret' => trim((string) config('services.google_drive.client_secret')),
                'refresh_token' => trim((string) config('services.google_drive.refresh_token')),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Drive OAuth token failed HTTP '.$response->status().': '.$response->body()
            );
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('Google Drive OAuth token response missing access_token.');
        }

        return $token;
    }

    private function fetchServiceAccountAccessToken(): string
    {
        $creds = $this->serviceAccountCredentials();
        if ($creds === null) {
            throw new RuntimeException('Google Drive service account JSON is missing or invalid.');
        }

        $now = time();
        $scope = implode(' ', [
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/spreadsheets',
        ]);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $creds['client_email'],
            'scope' => $scope,
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        ], JSON_THROW_ON_ERROR));

        $unsigned = $header.'.'.$claims;
        $signature = '';
        $ok = openssl_sign($unsigned, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256);
        if (! $ok) {
            throw new RuntimeException('Failed to sign Google service account JWT.');
        }

        $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);
        $tokenUrl = (string) config('services.google_drive.oauth_token_url', 'https://oauth2.googleapis.com/token');

        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(20)
            ->post($tokenUrl, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Google Drive SA token failed HTTP '.$response->status().': '.$response->body()
            );
        }

        $token = trim((string) $response->json('access_token'));
        if ($token === '') {
            throw new RuntimeException('Google Drive SA token response missing access_token.');
        }

        return $token;
    }

    /**
     * @return array{client_email: string, private_key: string}|null
     */
    private function serviceAccountCredentials(): ?array
    {
        $raw = trim((string) config('services.google_drive.service_account_json'));
        if ($raw === '') {
            return null;
        }

        if (is_file($raw)) {
            $raw = (string) file_get_contents($raw);
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return null;
        }

        $email = trim((string) ($decoded['client_email'] ?? ''));
        $key = (string) ($decoded['private_key'] ?? '');
        if ($email === '' || $key === '') {
            return null;
        }

        return [
            'client_email' => $email,
            'private_key' => $key,
        ];
    }

    private function authMode(): string
    {
        return strtolower(trim((string) config('services.google_drive.auth', 'service_account')));
    }

    private function timezone(): string
    {
        $tz = trim((string) config('services.google_drive.timezone', 'America/Los_Angeles'));

        return $tz !== '' ? $tz : 'America/Los_Angeles';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        return $letter !== '' ? $letter : 'A';
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
