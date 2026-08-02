<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RingCentralCall;
use Illuminate\Support\Facades\Schema;

final class CallTranscriptionQueue
{
    public function __construct(
        private readonly CallTranscriptionEligibility $eligibility,
    ) {}

    /**
     * Mark a call pending for transcription. Never re-queues completed unless $force.
     */
    public function enqueue(RingCentralCall $call, bool $force = false): bool
    {
        if (! Schema::hasColumn('ringcentral_calls', 'transcript_status')) {
            return false;
        }

        $call->refresh();

        if (! $this->eligibility->isEligible($call, force: $force)) {
            return false;
        }

        if (
            ! $force
            && in_array($call->transcript_status, [
                RingCentralCall::TRANSCRIPT_PENDING,
                RingCentralCall::TRANSCRIPT_PROCESSING,
                RingCentralCall::TRANSCRIPT_COMPLETED,
                RingCentralCall::TRANSCRIPT_FAILED,
            ], true)
        ) {
            return false;
        }

        $call->forceFill([
            'transcript_status' => RingCentralCall::TRANSCRIPT_PENDING,
            'transcript_queued_at' => now(),
            'transcript_error' => null,
        ])->save();

        return true;
    }

    public function enqueueIfEligible(RingCentralCall $call): bool
    {
        return $this->enqueue($call, force: false);
    }
}
