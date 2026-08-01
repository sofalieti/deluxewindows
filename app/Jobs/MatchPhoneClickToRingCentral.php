<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PhoneClick;
use App\Services\RingCentralCallLogService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MatchPhoneClickToRingCentral implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(
        public readonly int $phoneClickId,
        public readonly bool $force = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(RingCentralCallLogService $ringCentral): void
    {
        Cache::lock('ringcentral:phone-click:'.$this->phoneClickId, 55)->get(
            function () use ($ringCentral): void {
                $click = PhoneClick::query()->find($this->phoneClickId);
                if (! $click || $click->isSpam()) {
                    return;
                }

                if ($this->force) {
                    $meta = is_array($click->meta) ? $click->meta : [];
                    unset($meta['ringcentral_match_lag_seconds']);
                    $click->forceFill([
                        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
                        'ringcentral_checked_at' => null,
                        'ringcentral_call_id' => null,
                        'ringcentral_session_id' => null,
                        'ringcentral_result' => null,
                        'ringcentral_direction' => null,
                        'ringcentral_call_started_at' => null,
                        'ringcentral_duration' => null,
                        'ringcentral_from_phone' => null,
                        'ringcentral_to_phone' => null,
                        'ringcentral_error' => null,
                        'meta' => $meta,
                    ])->saveQuietly();
                }

                if ($click->hasFinalRingCentralStatus()) {
                    return;
                }

                $appTimezone = (string) config('app.timezone', 'America/Los_Angeles');
                $now = CarbonImmutable::now($appTimezone);
                $checkedAt = $click->ringcentral_checked_at
                    ? CarbonImmutable::parse($click->ringcentral_checked_at)
                    : null;
                if (
                    $checkedAt
                    && $click->ringcentral_attempts > 0
                    && $checkedAt->greaterThan($now->subSeconds(30))
                    && $checkedAt->lessThanOrEqualTo($now->addSeconds(5))
                ) {
                    return;
                }

                $windowMinutes = max(3, (int) config('services.ringcentral.match_window_minutes', 10));
                $deadline = CarbonImmutable::parse($click->created_at)->addMinutes($windowMinutes);

                $click->forceFill([
                    'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
                    'ringcentral_checked_at' => $now,
                    'ringcentral_attempts' => ((int) $click->ringcentral_attempts) + 1,
                    'ringcentral_error' => null,
                ])->save();

                try {
                    $match = $ringCentral->findMatchingCall($click);
                } catch (\Throwable $exception) {
                    $this->handleApiFailure($click, $deadline, $now, $exception);

                    return;
                }

                if ($match !== null) {
                    try {
                        $meta = is_array($click->meta) ? $click->meta : [];
                        $meta['ringcentral_match_lag_seconds'] = (int) ($match['lag_seconds'] ?? 0);

                        $click->forceFill([
                            'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
                            'ringcentral_checked_at' => $now,
                            'ringcentral_call_id' => $match['id'],
                            'ringcentral_session_id' => $match['session_id'],
                            'ringcentral_result' => $match['result'],
                            'ringcentral_direction' => $match['direction'],
                            'ringcentral_call_started_at' => $match['start_time']->setTimezone($appTimezone),
                            'ringcentral_duration' => $match['duration'],
                            'ringcentral_from_phone' => $match['from_phone'],
                            'ringcentral_to_phone' => $match['to_phone'],
                            'ringcentral_error' => null,
                            'meta' => $meta,
                        ])->save();
                    } catch (\Throwable $exception) {
                        Log::warning('RingCentral call could not be assigned to phone click', [
                            'phone_click_id' => $click->id,
                            'error' => $exception->getMessage(),
                        ]);

                        $this->scheduleRetryOrFinish(
                            $click,
                            $deadline,
                            $now,
                            'A matching RingCentral call was already assigned.'
                        );
                    }

                    return;
                }

                $this->scheduleRetryOrFinish($click, $deadline, $now);
            }
        );
    }

    public function failed(?\Throwable $exception): void
    {
        PhoneClick::query()
            ->whereKey($this->phoneClickId)
            ->whereNotIn('ringcentral_status', [
                PhoneClick::RINGCENTRAL_FOUND,
                PhoneClick::RINGCENTRAL_NO_CALL,
            ])
            ->update([
                'ringcentral_status' => PhoneClick::RINGCENTRAL_ERROR,
                'ringcentral_checked_at' => now(),
                'ringcentral_error' => Str::limit(
                    $exception?->getMessage() ?: 'RingCentral queue job failed.',
                    1000,
                    ''
                ),
            ]);
    }

    private function handleApiFailure(
        PhoneClick $click,
        CarbonImmutable $deadline,
        CarbonImmutable $now,
        \Throwable $exception
    ): void {
        Log::warning('RingCentral phone click lookup failed', [
            'phone_click_id' => $click->id,
            'attempt' => $click->ringcentral_attempts,
            'error' => $exception->getMessage(),
        ]);

        $this->scheduleRetryOrFinish($click, $deadline, $now, $exception->getMessage(), true);
    }

    private function scheduleRetryOrFinish(
        PhoneClick $click,
        CarbonImmutable $deadline,
        CarbonImmutable $now,
        ?string $error = null,
        bool $apiFailed = false
    ): void {
        if ($now->greaterThanOrEqualTo($deadline)) {
            $click->forceFill([
                'ringcentral_status' => $apiFailed
                    ? PhoneClick::RINGCENTRAL_ERROR
                    : PhoneClick::RINGCENTRAL_NO_CALL,
                'ringcentral_checked_at' => $now,
                'ringcentral_error' => $error ? Str::limit($error, 1000, '') : null,
            ])->save();

            return;
        }

        $configuredDelay = max(30, (int) config('services.ringcentral.retry_delay_seconds', 120));
        $secondsUntilDeadline = max(1, (int) $now->diffInSeconds($deadline));
        $delaySeconds = min($configuredDelay, $secondsUntilDeadline);

        $click->forceFill([
            'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
            'ringcentral_error' => $error ? Str::limit($error, 1000, '') : null,
        ])->save();

        self::dispatch($click->id)
            ->delay(now()->addSeconds($delaySeconds))
            ->afterCommit();
    }
}
