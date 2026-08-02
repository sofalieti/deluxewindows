<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Services\RingCentralCallLogService;
use Illuminate\Http\Response;
use Throwable;

class RingCentralRecordingController extends Controller
{
    public function forCall(RingCentralCall $call, RingCentralCallLogService $ringCentral): Response
    {
        $recordingId = $call->resolvedRecordingId();
        abort_if($recordingId === null, 404, 'No recording for this call.');

        return $this->stream($ringCentral, $recordingId, 'call-'.$call->id);
    }

    public function forPhoneClick(PhoneClick $click, RingCentralCallLogService $ringCentral): Response
    {
        $recordingId = $click->resolvedRecordingId();
        abort_if($recordingId === null, 404, 'No recording for this phone click.');

        return $this->stream($ringCentral, $recordingId, 'phone-click-'.$click->id);
    }

    private function stream(
        RingCentralCallLogService $ringCentral,
        string $recordingId,
        string $filenameBase,
    ): Response {
        try {
            $upstream = $ringCentral->fetchRecordingContent($recordingId);
        } catch (Throwable $exception) {
            report($exception);
            abort(502, 'Could not load RingCentral recording.');
        }

        if (! $upstream->successful()) {
            abort($upstream->status() === 404 ? 404 : 502, 'RingCentral recording unavailable.');
        }

        $contentType = (string) ($upstream->header('Content-Type') ?: 'audio/mpeg');

        return response($upstream->body(), 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'inline; filename="'.$filenameBase.'.mp3"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
