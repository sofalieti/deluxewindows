<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RingCentralCall;
use App\Models\RingCentralCallSyncState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class RingCentralCallSyncService
{
    public function __construct(
        private readonly RingCentralCallLogService $callLog,
        private readonly PromotionControlService $promotions,
    ) {}

    /**
     * @return array{
     *     created: int,
     *     updated: int,
     *     fetched: int,
     *     from: string,
     *     to: string,
     *     business_phone: string,
     *     phones: list<string>
     * }
     */
    public function sync(?CarbonImmutable $now = null): array
    {
        $now = ($now ?? CarbonImmutable::now('UTC'))->utc();
        $phones = $this->promotions->ringCentralPhones();
        if ($phones === []) {
            throw new RuntimeException('No admin phone numbers are configured for RingCentral sync.');
        }

        $created = 0;
        $updated = 0;
        $fetched = 0;
        $earliestFrom = null;
        $syncedPhones = [];

        foreach ($phones as $businessPhone) {
            $result = $this->syncPhone($businessPhone, $now);
            $created += $result['created'];
            $updated += $result['updated'];
            $fetched += $result['fetched'];
            $syncedPhones[] = $businessPhone;

            $from = CarbonImmutable::parse($result['from']);
            if ($earliestFrom === null || $from->lessThan($earliestFrom)) {
                $earliestFrom = $from;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'fetched' => $fetched,
            'from' => ($earliestFrom ?? $now)->toIso8601String(),
            'to' => $now->toIso8601String(),
            'business_phone' => implode(', ', $syncedPhones),
            'phones' => $syncedPhones,
        ];
    }

    /**
     * @return array{created: int, updated: int, fetched: int, from: string, to: string, business_phone: string}
     */
    private function syncPhone(string $businessPhone, CarbonImmutable $now): array
    {
        $businessPhone = $this->callLog->normalizePhone($businessPhone);
        if ($businessPhone === '') {
            throw new RuntimeException('The current admin phone number is empty.');
        }

        $initialStart = $now
            ->setTimezone('America/Los_Angeles')
            ->startOfDay()
            ->utc();

        $state = RingCentralCallSyncState::query()->firstOrCreate(
            ['business_phone' => $businessPhone],
            ['started_at' => $initialStart],
        );

        $startedAt = CarbonImmutable::parse($state->started_at)->utc();
        $dateFrom = $state->last_synced_at
            ? CarbonImmutable::parse($state->last_synced_at)->utc()->subMinutes(5)
            : $startedAt;
        if ($dateFrom->lessThan($startedAt)) {
            $dateFrom = $startedAt;
        }

        if ($dateFrom->greaterThan($now)) {
            $dateFrom = $now;
        }

        $records = $this->callLog->fetchCalls($businessPhone, $dateFrom, $now);
        $created = 0;
        $updated = 0;

        DB::transaction(function () use (
            $records,
            $state,
            $now,
            &$created,
            &$updated
        ): void {
            foreach ($records as $record) {
                $call = RingCentralCall::query()->firstOrNew([
                    'ringcentral_call_id' => $record['ringcentral_call_id'],
                ]);
                $call->exists ? $updated++ : $created++;
                $call->fill($record);
                $call->synced_at = $now;
                $call->save();
            }

            $state->last_synced_at = $now;
            $state->save();
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'fetched' => count($records),
            'from' => $dateFrom->toIso8601String(),
            'to' => $now->toIso8601String(),
            'business_phone' => $businessPhone,
        ];
    }
}
