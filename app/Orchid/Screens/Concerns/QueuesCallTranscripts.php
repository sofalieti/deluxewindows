<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Concerns;

use App\Models\RingCentralCall;
use App\Services\CallTranscriptionQueue;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;

trait QueuesCallTranscripts
{
    public function queueTranscript(Request $request, CallTranscriptionQueue $queue): void
    {
        $validated = $request->validate([
            'call' => ['required', 'integer', 'exists:ringcentral_calls,id'],
        ]);

        $call = RingCentralCall::query()->findOrFail((int) $validated['call']);

        if ($queue->enqueue($call, force: true)) {
            Toast::success('Call queued for transcription. It will process within a few minutes.');

            return;
        }

        Toast::warning('Could not queue this call. It needs a recording and a contact, lead, or phone-click link.');
    }
}
