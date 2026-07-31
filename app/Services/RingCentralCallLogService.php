<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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
        $candidatePhones = $this->matchCandidatePhones((string) $click->phone);
        if ($candidatePhones === []) {
            return null;
        }

        $startedAt = CarbonImmutable::parse($click->created_at)->utc();
        $tolerance = max(0, (int) config('services.ringcentral.clock_tolerance_seconds', 30));
        $dateFrom = $startedAt->subSeconds($tolerance);
        $windowMinutes = max(3, (int) config('services.ringcentral.match_window_minutes', 10));
        $deadline = $startedAt->addMinutes($windowMinutes);
        $currentTime = CarbonImmutable::now('UTC');
        $dateTo = $currentTime->lessThan($deadline) ? $currentTime : $deadline;

        // Prefer already-synced journal (works even when API phoneNumber= is empty).
        $fromJournal = $this->findMatchingCallInJournal($click, $candidatePhones, $startedAt, $dateFrom, $dateTo);
        if ($fromJournal !== null) {
            return $fromJournal;
        }

        // Account-wide API + local filter (phoneNumber= returns 0 on this RC account).
        $matches = [];
        $seenCallIds = [];
        foreach ($this->fetchAccountVoiceRecords($dateFrom, $dateTo) as $record) {
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
                || isset($seenCallIds[$id])
                || strcasecmp($direction, 'Inbound') !== 0
                || ($type !== '' && strcasecmp($type, 'Voice') !== 0)
                || ! $this->matchesAnyPhone($toPhone, $candidatePhones)
                || $rawStartTime === ''
            ) {
                continue;
            }

            $seenCallIds[$id] = true;

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

    /**
     * @param  list<string>  $candidatePhones
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
    private function findMatchingCallInJournal(
        PhoneClick $click,
        array $candidatePhones,
        CarbonImmutable $startedAt,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo,
    ): ?array {
        if (! Schema::hasTable('ringcentral_calls')) {
            return null;
        }

        $calls = RingCentralCall::query()
            ->where('direction', 'Inbound')
            ->where('started_at', '>=', $dateFrom->subHour()->format('Y-m-d H:i:s'))
            ->where('started_at', '<=', $dateTo->addHour()->format('Y-m-d H:i:s'))
            ->orderBy('started_at')
            ->limit(200)
            ->get();

        $matches = [];
        foreach ($calls as $call) {
            $toPhone = $this->normalizePhone((string) ($call->to_phone ?: $call->business_phone));
            if (! $this->matchesAnyPhone($toPhone, $candidatePhones)) {
                continue;
            }

            $id = trim((string) $call->ringcentral_call_id);
            if ($id === '') {
                continue;
            }

            if (PhoneClick::query()
                ->where('ringcentral_call_id', $id)
                ->where($click->getKeyName(), '!=', $click->getKey())
                ->exists()) {
                continue;
            }

            try {
                $callStartedAt = CarbonImmutable::parse($call->getRawOriginal('started_at') ?: $call->started_at)
                    ->utc();
            } catch (\Throwable) {
                continue;
            }

            if ($callStartedAt->lessThan($dateFrom) || $callStartedAt->greaterThan($dateTo)) {
                continue;
            }

            $matches[] = [
                'id' => $id,
                'session_id' => trim((string) (
                    $call->telephony_session_id ?: $call->session_id ?: ''
                )),
                'result' => trim((string) ($call->result ?: 'Unknown')),
                'direction' => 'Inbound',
                'start_time' => $callStartedAt,
                'duration' => max(0, (int) $call->duration),
                'from_phone' => $this->normalizePhone((string) $call->from_phone),
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

    /**
     * Clicked number plus every monitored company number from Promotions.
     *
     * @return list<string>
     */
    public function matchCandidatePhones(string $clickedPhone): array
    {
        $phones = [];
        $clicked = $this->normalizePhone($clickedPhone);
        if ($clicked !== '') {
            $phones[] = $clicked;
        }

        foreach (app(PromotionControlService::class)->ringCentralPhones() as $phone) {
            $normalized = $this->normalizePhone($phone);
            if ($normalized === '' || $this->matchesAnyPhone($normalized, $phones)) {
                continue;
            }
            $phones[] = $normalized;
        }

        return $phones;
    }

    /**
     * @param  list<string>  $phones
     */
    public function matchesAnyPhone(string $needle, array $phones): bool
    {
        foreach ($phones as $phone) {
            if ($this->phonesMatch($needle, $phone)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{
     *     ringcentral_call_id: string,
     *     session_id: string,
     *     telephony_session_id: string,
     *     direction: string,
     *     action: string,
     *     result: string,
     *     started_at: CarbonImmutable,
     *     duration: int,
     *     business_phone: string,
     *     from_phone: string,
     *     from_name: string,
     *     to_phone: string,
     *     to_name: string,
     *     external_phone: string,
     *     raw: array<string, mixed>
     * }>
     */
    public function fetchCalls(
        string $businessPhone,
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): array {
        $businessPhone = $this->normalizePhone($businessPhone);
        if ($businessPhone === '') {
            throw new RuntimeException('The current admin phone number is empty.');
        }

        // Account-wide fetch + local phone filter. RingCentral's phoneNumber=
        // filter can return empty even when account-wide log contains the calls.
        $records = $this->fetchAccountVoiceRecords($dateFrom, $dateTo);
        $calls = [];

        foreach ($records as $record) {
            $call = $this->normalizeCallRecord($record, $businessPhone);
            if ($call !== null) {
                $calls[$call['ringcentral_call_id']] = $call;
            }
        }

        return array_values($calls);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchAccountVoiceRecords(
        CarbonImmutable $dateFrom,
        CarbonImmutable $dateTo
    ): array {
        $url = $this->baseUrl().'/restapi/v1.0/account/'
            .(trim((string) config('services.ringcentral.account_id', '~')) ?: '~')
            .'/call-log';
        $query = [
            'view' => 'Simple',
            'type' => 'Voice',
            'dateFrom' => $dateFrom->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'dateTo' => $dateTo->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'perPage' => 100,
        ];
        $records = [];
        $page = 0;

        do {
            $response = $this->callLogResponse($url, $query);
            $pageRecords = $response->json('records', []);
            if (is_array($pageRecords)) {
                foreach ($pageRecords as $record) {
                    if (is_array($record)) {
                        $records[] = $record;
                    }
                }
            }

            $nextUrl = trim((string) $response->json('navigation.nextPage.uri', ''));
            $url = $nextUrl;
            $query = [];
            $page++;
        } while ($url !== '' && $page < 100);

        return $records;
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

    private function callLogResponse(string $url, array $query = []): Response
    {
        $response = $this->sendAuthorizedGet($url, $query);

        if ($response->status() === 401) {
            Cache::forget($this->tokenCacheKey());
            $response = $this->sendAuthorizedGet($url, $query);
        }

        if (! $response->successful()) {
            throw new RuntimeException('RingCentral call log returned HTTP '.$response->status().'.');
        }

        return $response;
    }

    private function sendAuthorizedGet(string $url, array $query): Response
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 250, throw: false)
            ->get($url, $query);
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

    /**
     * @return array{
     *     ringcentral_call_id: string,
     *     session_id: string,
     *     telephony_session_id: string,
     *     direction: string,
     *     action: string,
     *     result: string,
     *     started_at: CarbonImmutable,
     *     duration: int,
     *     business_phone: string,
     *     from_phone: string,
     *     from_name: string,
     *     to_phone: string,
     *     to_name: string,
     *     external_phone: string,
     *     raw: array<string, mixed>
     * }|null
     */
    public function normalizeCallRecord(mixed $record, string $businessPhone): ?array
    {
        if (! is_array($record) || strcasecmp((string) ($record['type'] ?? ''), 'Voice') !== 0) {
            return null;
        }

        $id = trim((string) ($record['id'] ?? ''));
        $direction = ucfirst(strtolower(trim((string) ($record['direction'] ?? ''))));
        $fromPhone = $this->normalizePhone((string) data_get($record, 'from.phoneNumber', ''));
        $toPhone = $this->normalizePhone((string) data_get($record, 'to.phoneNumber', ''));
        $rawStartedAt = trim((string) ($record['startTime'] ?? ''));

        if ($id === '' || $rawStartedAt === '' || ! in_array($direction, ['Inbound', 'Outbound'], true)) {
            return null;
        }

        $externalPhone = $direction === 'Inbound' ? $fromPhone : $toPhone;
        $businessSide = $direction === 'Inbound' ? $toPhone : $fromPhone;
        if (! $this->phonesMatch($businessSide, $businessPhone) || $externalPhone === '') {
            return null;
        }

        try {
            $startedAt = CarbonImmutable::parse($rawStartedAt)->utc();
        } catch (\Throwable) {
            return null;
        }

        return [
            'ringcentral_call_id' => $id,
            'session_id' => trim((string) ($record['sessionId'] ?? '')),
            'telephony_session_id' => trim((string) ($record['telephonySessionId'] ?? '')),
            'direction' => $direction,
            'action' => trim((string) ($record['action'] ?? '')),
            'result' => trim((string) ($record['result'] ?? '')),
            'started_at' => $startedAt,
            'duration' => max(0, (int) ($record['duration'] ?? 0)),
            'business_phone' => $businessPhone,
            'from_phone' => $fromPhone,
            'from_name' => trim((string) data_get($record, 'from.name', '')),
            'to_phone' => $toPhone,
            'to_name' => trim((string) data_get($record, 'to.name', '')),
            'external_phone' => $externalPhone,
            'raw' => $record,
        ];
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

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            $digits = '1'.$digits;
        }

        return $digits !== '' ? '+'.$digits : '';
    }

    /**
     * Compare numbers ignoring formatting: "(650) 461-4446", "6504614446", "+16504614446".
     */
    public function phonesMatch(string $left, string $right): bool
    {
        $left = $this->normalizePhone($left);
        $right = $this->normalizePhone($right);
        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        $leftDigits = substr(preg_replace('/\D+/', '', $left) ?? '', -10);
        $rightDigits = substr(preg_replace('/\D+/', '', $right) ?? '', -10);

        return strlen($leftDigits) === 10
            && strlen($rightDigits) === 10
            && $leftDigits === $rightDigits;
    }
}
