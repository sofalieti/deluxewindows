<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RingCentralCall;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CallTranscriptionProcessor
{
    public function __construct(
        private readonly CallTranscriptionService $transcription,
        private readonly CallTranscriptionEligibility $eligibility,
    ) {}

    /**
     * Process at most one pending call. Returns null when idle.
     *
     * @return array{call_id: int, status: string}|null
     */
    public function processNext(): ?array
    {
        if (! Schema::hasColumn('ringcentral_calls', 'transcript_status')) {
            return null;
        }

        $this->failStuckProcessing();

        if (RingCentralCall::query()
            ->where('transcript_status', RingCentralCall::TRANSCRIPT_PROCESSING)
            ->exists()) {
            return null;
        }

        $call = RingCentralCall::query()
            ->where('transcript_status', RingCentralCall::TRANSCRIPT_PENDING)
            ->orderBy('transcript_queued_at')
            ->orderBy('id')
            ->first();

        if ($call === null) {
            return null;
        }

        if (! $this->eligibility->isEligible($call, force: true)) {
            // Force=true only skips completed check; still need recording/link.
            // If no longer eligible, mark skipped so it does not block the queue.
            if ($call->transcript_status !== RingCentralCall::TRANSCRIPT_COMPLETED) {
                $call->forceFill([
                    'transcript_status' => RingCentralCall::TRANSCRIPT_SKIPPED,
                    'transcript_error' => 'No longer eligible for transcription.',
                    'transcript_processed_at' => now(),
                ])->save();
            }

            return [
                'call_id' => (int) $call->id,
                'status' => RingCentralCall::TRANSCRIPT_SKIPPED,
            ];
        }

        try {
            $this->transcription->process($call, force: true);
            $call->refresh();

            return [
                'call_id' => (int) $call->id,
                'status' => (string) $call->transcript_status,
            ];
        } catch (Throwable) {
            $call->refresh();

            return [
                'call_id' => (int) $call->id,
                'status' => (string) ($call->transcript_status ?: RingCentralCall::TRANSCRIPT_FAILED),
            ];
        }
    }

    private function failStuckProcessing(): void
    {
        $minutes = max(5, (int) config('services.openai.transcript_stuck_minutes', 15));
        $cutoff = CarbonImmutable::now()->subMinutes($minutes);

        RingCentralCall::query()
            ->where('transcript_status', RingCentralCall::TRANSCRIPT_PROCESSING)
            ->where(function ($query) use ($cutoff): void {
                $query->where('updated_at', '<', $cutoff)
                    ->orWhere(function ($inner) use ($cutoff): void {
                        $inner->whereNotNull('transcript_queued_at')
                            ->where('transcript_queued_at', '<', $cutoff);
                    });
            })
            ->update([
                'transcript_status' => RingCentralCall::TRANSCRIPT_FAILED,
                'transcript_error' => 'Transcription timed out while processing.',
                'transcript_processed_at' => now(),
            ]);
    }
}
