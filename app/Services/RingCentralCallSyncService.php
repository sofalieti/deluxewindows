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

    private const DEFAULT_HISTORY_DAYS = 7;

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
    public function sync(?CarbonImmutable $now = null, ?int $forceDays = null): array
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

        // One account-wide pull for the widest phone window, then filter per number.
        $windows = [];
        foreach ($phones as $businessPhone) {
            $normalized = $this->callLog->normalizePhone($businessPhone);
            if ($normalized === '') {
                continue;
            }
            $windows[$normalized] = $this->resolveWindow($normalized, $now, $forceDays);
        }

        if ($windows === []) {
            throw new RuntimeException('No admin phone numbers are configured for RingCentral sync.');
        }

        $globalFrom = null;
        foreach ($windows as $window) {
            if ($globalFrom === null || $window['date_from']->lessThan($globalFrom)) {
                $globalFrom = $window['date_from'];
            }
        }

        $accountRecords = $this->callLog->fetchAccountVoiceRecords($globalFrom ?? $now, $now);

        foreach ($windows as $businessPhone => $window) {
            $result = $this->syncPhoneFromRecords(
                $businessPhone,
                $window,
                $now,
                $accountRecords,
            );
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
     * @return array{date_from: CarbonImmutable, history_start: CarbonImmutable, previous_checkpoint: ?CarbonImmutable}
     */
    private function resolveWindow(
        string $businessPhone,
        CarbonImmutable $now,
        ?int $forceDays
    ): array {
        $historyStart = $now
            ->subDays(self::DEFAULT_HISTORY_DAYS)
            ->utc();

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', $businessPhone)
            ->first();

        if ($state === null) {
            $id = RingCentralCallSyncState::query()->insertGetId([
                'business_phone' => $businessPhone,
                'started_at' => $this->utcDatabaseString($historyStart),
                'last_synced_at' => null,
                'created_at' => $this->utcDatabaseString($now),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
            $state = RingCentralCallSyncState::query()->findOrFail($id);
        } else {
            $state->refresh();
        }

        $storedHistoryStart = $this->readUtcColumn($state, 'started_at') ?? $historyStart;
        if ($storedHistoryStart->greaterThan($historyStart)) {
            // Widen a too-short first window from older deploys (midnight-only).
            RingCentralCallSyncState::query()->whereKey($state->id)->update([
                'started_at' => $this->utcDatabaseString($historyStart),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
            $storedHistoryStart = $historyStart;
        }

        $previousCheckpoint = $this->readUtcColumn($state, 'last_synced_at');
        $storedCallCount = RingCentralCall::query()
            ->where('business_phone', $businessPhone)
            ->count();

        if ($forceDays !== null) {
            $dateFrom = $now->subDays(max(1, $forceDays));
            if ($dateFrom->lessThan($storedHistoryStart)) {
                $dateFrom = $storedHistoryStart;
            }

            return [
                'date_from' => $dateFrom,
                'history_start' => $storedHistoryStart,
                'previous_checkpoint' => $previousCheckpoint,
            ];
        }

        // Empty local journal after a bad/empty sync: ignore checkpoint and backfill.
        if ($storedCallCount === 0 || $previousCheckpoint === null) {
            $dateFrom = $storedHistoryStart;
        } else {
            $dateFrom = $previousCheckpoint->subMinutes(self::OVERLAP_MINUTES);
        }

        if ($dateFrom->lessThan($storedHistoryStart)) {
            $dateFrom = $storedHistoryStart;
        }

        if ($dateFrom->greaterThan($now)) {
            $dateFrom = $now->subMinutes(self::OVERLAP_MINUTES);
            if ($dateFrom->lessThan($storedHistoryStart)) {
                $dateFrom = $storedHistoryStart;
            }
        }

        return [
            'date_from' => $dateFrom,
            'history_start' => $storedHistoryStart,
            'previous_checkpoint' => $previousCheckpoint,
        ];
    }

    /**
     * @param  array{date_from: CarbonImmutable, history_start: CarbonImmutable, previous_checkpoint: ?CarbonImmutable}  $window
     * @param  list<array<string, mixed>>  $accountRecords
     * @return array{created: int, updated: int, fetched: int, from: string, to: string, business_phone: string}
     */
    private function syncPhoneFromRecords(
        string $businessPhone,
        array $window,
        CarbonImmutable $now,
        array $accountRecords,
    ): array {
        $dateFrom = $window['date_from'];
        $previousCheckpoint = $window['previous_checkpoint'];
        $records = [];

        foreach ($accountRecords as $record) {
            $call = $this->callLog->normalizeCallRecord($record, $businessPhone);
            if ($call === null) {
                continue;
            }
            if ($call['started_at']->lessThan($dateFrom) || $call['started_at']->greaterThan($now)) {
                continue;
            }
            $records[$call['ringcentral_call_id']] = $call;
        }

        $records = array_values($records);
        $created = 0;
        $updated = 0;

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', $businessPhone)
            ->firstOrFail();

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

            RingCentralCallSyncState::query()->whereKey($state->id)->update([
                'last_synced_at' => $this->utcDatabaseString($now),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'fetched' => count($records),
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
