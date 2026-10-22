<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;

/**
 * Re-runs RingCentral matching for older Microsoft Ads phone clicks so a call
 * that started within three minutes of the tap can still be attached.
 */
final class MicrosoftPhoneClickRecheck
{
    public function __construct(
        private readonly RingCentralCallLogService $ringCentral,
    ) {}

    /**
     * @return array{checked: int, matched: int, no_call: int, failed: int, remaining: int, last_error: ?string}
     */
    public function run(int $limit = 40, int $days = 30): array
    {
        $limit = max(1, $limit);
        $query = PhoneClick::query()
            ->notSpam()
            ->where('traffic_source', 'microsoft_ads')
            ->whereIn('ringcentral_status', [
                PhoneClick::RINGCENTRAL_PENDING,
                PhoneClick::RINGCENTRAL_ERROR,
                PhoneClick::RINGCENTRAL_NO_CALL,
                PhoneClick::RINGCENTRAL_NOT_CHECKED,
            ])
            ->where('created_at', '>=', now()->subDays(max(1, $days)));

        $total = (clone $query)->count();
        $clicks = (clone $query)->orderByDesc('id')->limit($limit)->get();

        $matched = 0;
        $noCall = 0;
        $failed = 0;
        $lastError = null;

        foreach ($clicks as $click) {
            (new MatchPhoneClickToRingCentral($click->id, force: true))->handle($this->ringCentral);
            $click->refresh();

            if ($click->ringcentral_status === PhoneClick::RINGCENTRAL_FOUND) {
                $matched++;
            } elseif ($click->ringcentral_status === PhoneClick::RINGCENTRAL_NO_CALL) {
                $noCall++;
            } else {
                $failed++;
                $error = trim((string) $click->ringcentral_error);
                if ($error !== '') {
                    $lastError = $error;
                }
            }
        }

        return [
            'checked' => $clicks->count(),
            'matched' => $matched,
            'no_call' => $noCall,
            'failed' => $failed,
            'remaining' => max(0, $total - $clicks->count()),
            'last_error' => $lastError,
        ];
    }
}
