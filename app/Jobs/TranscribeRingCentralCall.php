<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\RingCentralCall;
use App\Services\CallTranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TranscribeRingCentralCall implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 240;

    public function __construct(
        public readonly int $ringCentralCallId,
        public readonly bool $force = false,
    ) {}

    public function handle(CallTranscriptionService $transcription): void
    {
        $call = RingCentralCall::query()->find($this->ringCentralCallId);
        if ($call === null) {
            return;
        }

        $transcription->process($call, force: $this->force);
    }
}
