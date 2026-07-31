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
    private const OVERLAP_MINUTES = 5;

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

            $from = CarbonImmutable::parse($result['from'])->utc();
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

        $now = $now->utc();
        $initialStart = $now
            ->setTimezone('America/Los_Angeles')
            ->startOfDay()
            ->utc();

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', $businessPhone)
            ->first();

        if ($state === null) {
            $id = RingCentralCallSyncState::query()->insertGetId([
                'business_phone' => $businessPhone,
                'started_at' => $this->utcDatabaseString($initialStart),
                'last_synced_at' => null,
                'created_at' => $this->utcDatabaseString($now),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
            $state = RingCentralCallSyncState::query()->findOrFail($id);
        } else {
            $state->refresh();
        }

        $historyStart = $this->readUtcColumn($state, 'started_at') ?? $initialStart;
        $previousCheckpoint = $this->readUtcColumn($state, 'last_synced_at');

        if ($previousCheckpoint !== null) {
            $dateFrom = $previousCheckpoint->subMinutes(self::OVERLAP_MINUTES);
        } else {
            $dateFrom = $historyStart;
        }

        if ($dateFrom->lessThan($historyStart)) {
            $dateFrom = $historyStart;
        }

        if ($dateFrom->greaterThan($now)) {
            $dateFrom = $now->subMinutes(self::OVERLAP_MINUTES);
            if ($dateFrom->lessThan($historyStart)) {
                $dateFrom = $historyStart;
            }
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

            // Write UTC wall-clock directly to avoid Laravel app-timezone shift on cast.
            RingCentralCallSyncState::query()->whereKey($state->id)->update([
                'last_synced_at' => $this->utcDatabaseString($now),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'fetched' => count($records),
            // Toast: remembered previous sync (or first-run start) → now.
            'from' => ($previousCheckpoint ?? $dateFrom)->toIso8601String(),
            'to' => $now->toIso8601String(),
            'business_phone' => $businessPhone,
        ];
    }

    private function readUtcColumn(RingCentralCallSyncState $state, string $column): ?CarbonImmutable
    {
        $raw = $state->getRawOriginal($column);
        if ($raw === null || $raw === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s', (string) $raw, 'UTC');
    }

    private function utcDatabaseString(CarbonImmutable $utc): string
    {
        return $utc->utc()->format('Y-m-d H:i:s');
    }
}
