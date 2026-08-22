<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PhoneClick;
use App\Services\PhoneClickGoogleBridge;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Posts a phone click to the lead Google Sheet bridge (same destination as the
 * admin "Google" button), after RingCentral finishes with found or no_call.
 */
class SendPhoneClickToGoogleSheet implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public readonly int $phoneClickId,
    ) {
        $this->onQueue('default');
    }

    public function handle(PhoneClickGoogleBridge $bridge): void
    {
        $click = PhoneClick::query()->find($this->phoneClickId);
        if (! $click) {
            return;
        }

        $result = $bridge->sendOnceAutomatic($click);

        if ($result['already_sent'] || ($result['skipped'] ?? false)) {
            return;
        }

        if (! $result['ok']) {
            Log::warning('Automatic phone click Google Sheet send failed', [
                'phone_click_id' => $this->phoneClickId,
                'message' => $result['message'],
            ]);

            $message = strtolower($result['message']);

            // Missing config is not retryable — wait for ops to set lead_bridge.urls.
            if (str_contains($message, 'not configured')) {
                return;
            }

            // Retryable network / HTTP bridge failures.
            if (str_contains($message, 'connect') || str_contains($message, 'http')) {
                throw new \RuntimeException($result['message']);
            }
        }
    }
}
