<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RingCentralCall;
use App\Models\RingCentralCallSyncState;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class RingCentralCallSyncService
{
    public const ACCOUNT_SYNC_KEY = 'account';

    private const OVERLAP_MINUTES = 5;

    private const DEFAULT_HISTORY_DAYS = 7;

    public function __construct(
        private readonly RingCentralCallLogService $callLog,
        private readonly PromotionControlService $promotions,
        private readonly RingCentralContactBinder $contacts,
        private readonly CallTranscriptionQueue $transcriptQueue,
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
        $monitored = array_values(array_filter(
            array_map(
                fn (string $phone): string => $this->callLog->normalizePhone($phone),
                $this->promotions->ringCentralPhones(),
            ),
            fn (string $phone): bool => $phone !== '',
        ));

        $window = $this->resolveAccountWindow($now, $forceDays);
        $accountRecords = $this->callLog->fetchAccountVoiceRecords($window['date_from'], $now);
        $phoneIndex = $this->contacts->phoneIndex();

        $records = [];
        foreach ($accountRecords as $record) {
            $call = $this->callLog->normalizeAnyCallRecord($record);
            if ($call === null) {
                continue;
            }
            if ($call['started_at']->lessThan($window['date_from']) || $call['started_at']->greaterThan($now)) {
                continue;
            }

            $call['contact_id'] = Schema::hasColumn('ringcentral_calls', 'contact_id')
                ? $this->contacts->contactIdForPhone($call['external_phone'], $phoneIndex)
                : null;

            $records[$call['ringcentral_call_id']] = $call;
        }

        $records = array_values($records);
        $created = 0;
        $updated = 0;

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', self::ACCOUNT_SYNC_KEY)
            ->firstOrFail();

        DB::transaction(function () use (
            $records,
            $state,
            $now,
            $monitored,
            &$created,
            &$updated
        ): void {
            foreach ($records as $record) {
                $payload = $record;
                if (! Schema::hasColumn('ringcentral_calls', 'contact_id')) {
                    unset($payload['contact_id']);
                }
                if (! Schema::hasColumn('ringcentral_calls', 'recording_id')) {
                    unset($payload['recording_id']);
                }

                $call = RingCentralCall::query()->firstOrNew([
                    'ringcentral_call_id' => $payload['ringcentral_call_id'],
                ]);
                $call->exists ? $updated++ : $created++;

                // Keep a previously known recording if this sync payload has none yet.
                if (
                    empty($payload['recording_id'])
                    && $call->exists
                    && filled($call->recording_id)
                ) {
                    unset($payload['recording_id']);
                }

                $call->fill($payload);
                $call->synced_at = $now;
                $call->save();

                $this->transcriptQueue->enqueueIfEligible($call->fresh() ?? $call);
            }

            RingCentralCallSyncState::query()->whereKey($state->id)->update([
                'last_synced_at' => $this->utcDatabaseString($now),
                'updated_at' => $this->utcDatabaseString($now),
            ]);

            // Keep per-phone checkpoints warm for older tooling/UI labels.
            foreach ($monitored as $phone) {
                $phoneState = RingCentralCallSyncState::query()
                    ->where('business_phone', $phone)
                    ->first();
                if ($phoneState === null) {
                    RingCentralCallSyncState::query()->insert([
                        'business_phone' => $phone,
                        'started_at' => $this->utcDatabaseString($now->subDays(self::DEFAULT_HISTORY_DAYS)),
                        'last_synced_at' => $this->utcDatabaseString($now),
                        'created_at' => $this->utcDatabaseString($now),
                        'updated_at' => $this->utcDatabaseString($now),
                    ]);
                } else {
                    RingCentralCallSyncState::query()->whereKey($phoneState->id)->update([
                        'last_synced_at' => $this->utcDatabaseString($now),
                        'updated_at' => $this->utcDatabaseString($now),
                    ]);
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'fetched' => count($records),
            'from' => ($window['previous_checkpoint'] ?? $window['date_from'])->toIso8601String(),
            'to' => $now->toIso8601String(),
            'business_phone' => $monitored !== [] ? implode(', ', $monitored) : 'account',
            'phones' => $monitored,
        ];
    }

    /**
     * @return array{date_from: CarbonImmutable, history_start: CarbonImmutable, previous_checkpoint: ?CarbonImmutable}
     */
    private function resolveAccountWindow(CarbonImmutable $now, ?int $forceDays): array
    {
        $historyStart = $now
            ->subDays(self::DEFAULT_HISTORY_DAYS)
            ->utc();

        $state = RingCentralCallSyncState::query()
            ->where('business_phone', self::ACCOUNT_SYNC_KEY)
            ->first();

        if ($state === null) {
            $id = RingCentralCallSyncState::query()->insertGetId([
                'business_phone' => self::ACCOUNT_SYNC_KEY,
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
            RingCentralCallSyncState::query()->whereKey($state->id)->update([
                'started_at' => $this->utcDatabaseString($historyStart),
                'updated_at' => $this->utcDatabaseString($now),
            ]);
            $storedHistoryStart = $historyStart;
        }

        $previousCheckpoint = $this->readUtcColumn($state, 'last_synced_at');
        $storedCallCount = RingCentralCall::query()->count();

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
