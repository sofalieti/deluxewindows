<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PhoneClick;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RingCentralCallLogService
{
    private const JWT_GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    /**
     * @return array{
     *     id: string,
     *     session_id: string,
     *     result: string,
     *     direction: string,
     *     start_time: CarbonImmutable,
     *     duration: int,
     *     from_phone: string,
     *     to_phone: string
     * }|null
     */
    public function findMatchingCall(PhoneClick $click): ?array
    {
        $targetPhone = $this->normalizePhone((string) $click->phone);
        if ($targetPhone === '') {
            return null;
        }

        $startedAt = CarbonImmutable::parse($click->created_at)->utc();
        $tolerance = max(0, (int) config('services.ringcentral.clock_tolerance_seconds', 30));
        $dateFrom = $startedAt->subSeconds($tolerance);
        $windowMinutes = max(3, (int) config('services.ringcentral.match_window_minutes', 10));
        $deadline = $startedAt->addMinutes($windowMinutes);
        $currentTime = CarbonImmutable::now('UTC');
        $dateTo = $currentTime->lessThan($deadline) ? $currentTime : $deadline;

        $response = $this->getCallLog($targetPhone, $dateFrom, $dateTo);
        $records = $response->json('records', []);
        if (! is_array($records)) {
            return null;
        }

        $matches = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $id = trim((string) ($record['id'] ?? ''));
            $direction = trim((string) ($record['direction'] ?? ''));
            $type = trim((string) ($record['type'] ?? ''));
            $toPhone = $this->normalizePhone((string) data_get($record, 'to.phoneNumber', ''));
            $rawStartTime = trim((string) ($record['startTime'] ?? ''));

            if (
                $id === ''
                || strcasecmp($direction, 'Inbound') !== 0
                || ($type !== '' && strcasecmp($type, 'Voice') !== 0)
                || $toPhone !== $targetPhone
                || $rawStartTime === ''
            ) {
                continue;
            }

            if (PhoneClick::query()
                ->where('ringcentral_call_id', $id)
                ->where($click->getKeyName(), '!=', $click->getKey())
                ->exists()) {
                continue;
            }

            try {
                $callStartedAt = CarbonImmutable::parse($rawStartTime)->utc();
            } catch (\Throwable) {
                continue;
            }

            if ($callStartedAt->lessThan($dateFrom) || $callStartedAt->greaterThan($dateTo)) {
                continue;
            }

            $matches[] = [
                'id' => $id,
                'session_id' => trim((string) (
                    $record['telephonySessionId']
                    ?? $record['sessionId']
                    ?? ''
                )),
                'result' => trim((string) ($record['result'] ?? 'Unknown')),
                'direction' => $direction,
                'start_time' => $callStartedAt,
                'duration' => max(0, (int) ($record['duration'] ?? 0)),
                'from_phone' => $this->normalizePhone((string) data_get($record, 'from.phoneNumber', '')),
                'to_phone' => $toPhone,
                'distance' => abs($startedAt->diffInSeconds($callStartedAt, false)),
            ];
        }

        if ($matches === []) {
            return null;
        }

        usort($matches, fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);
        unset($matches[0]['distance']);

        return $matches[0];
    }

    private function getCallLog(
        string $phone,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): Response {
        $response = $this->sendCallLogRequest($this->accessToken(), $phone, $dateFrom, $dateTo);

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());
            $response = $this->sendCallLogRequest($this->accessToken(), $phone, $dateFrom, $dateTo);
        }

        if (! $response->successful()) {
            throw new RuntimeException('RingCentral call log returned HTTP '.$response->status().'.');
        }

        return $response;
    }

    private function sendCallLogRequest(
        string $accessToken,
        string $phone,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): Response {
        $accountId = trim((string) config('services.ringcentral.account_id', '~')) ?: '~';
        $url = $this->baseUrl().'/restapi/v1.0/account/'.$accountId.'/call-log';

        return Http::withToken($accessToken)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 250, throw: false)
            ->get($url, [
                'view' => 'Simple',
                'type' => 'Voice',
                'direction' => 'Inbound',
                'phoneNumber' => $phone,
                'dateFrom' => $dateFrom->format('Y-m-d\TH:i:s.v\Z'),
                'dateTo' => $dateTo->format('Y-m-d\TH:i:s.v\Z'),
                'perPage' => 100,
            ]);
    }

    private function accessToken(): string
    {
        $cacheKey = $this->tokenCacheKey();
        $cached = Cache::get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $clientId = trim((string) config('services.ringcentral.client_id'));
        $clientSecret = trim((string) config('services.ringcentral.client_secret'));
        $jwt = trim((string) config('services.ringcentral.jwt'));

        if ($clientId === '' || $clientSecret === '' || $jwt === '') {
            throw new RuntimeException('RingCentral credentials are not configured.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->withBasicAuth($clientId, $clientSecret)
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 250, throw: false)
            ->post($this->baseUrl().'/restapi/oauth/token', [
                'grant_type' => self::JWT_GRANT_TYPE,
                'assertion' => $jwt,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('RingCentral authentication returned HTTP '.$response->status().'.');
        }

        $accessToken = trim((string) $response->json('access_token'));
        if ($accessToken === '') {
            throw new RuntimeException('RingCentral authentication returned no access token.');
        }

        $expiresIn = max(120, (int) $response->json('expires_in', 3600));
        Cache::put($cacheKey, $accessToken, max(60, $expiresIn - 120));

        return $accessToken;
    }

    private function tokenCacheKey(): string
    {
        return 'ringcentral:access-token:'.hash('sha256', (string) config('services.ringcentral.client_id'));
    }

    private function baseUrl(): string
    {
        return rtrim((string) config(
            'services.ringcentral.base_url',
            'https://platform.ringcentral.com'
        ), '/');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '';
    }
}
