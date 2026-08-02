<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use Illuminate\Support\Facades\Schema;

final class CallTranscriptionEligibility
{
    public function __construct(
        private readonly RingCentralCallLogService $callLog,
    ) {}

    public function minDurationSeconds(): int
    {
        return max(1, (int) config('services.openai.transcript_min_duration_seconds', 20));
    }

    public function isEligible(RingCentralCall $call, bool $force = false): bool
    {
        if (! Schema::hasColumn('ringcentral_calls', 'transcript_status')) {
            return false;
        }

        if (! $force && $call->transcript_status === RingCentralCall::TRANSCRIPT_COMPLETED) {
            return false;
        }

        if ($call->resolvedRecordingId() === null) {
            return false;
        }

        if ((int) $call->duration < $this->minDurationSeconds()) {
            return false;
        }

        return $this->isLinkedToCrm($call);
    }

    public function isLinkedToCrm(RingCentralCall $call): bool
    {
        if ($call->contact_id) {
            return true;
        }

        $callId = trim((string) ($call->ringcentral_call_id ?? ''));
        if ($callId !== '' && Schema::hasTable('phone_clicks')) {
            if (PhoneClick::query()->where('ringcentral_call_id', $callId)->exists()) {
                return true;
            }
        }

        return $this->matchesNonSpamLead($call);
    }

    public function matchesNonSpamLead(RingCentralCall $call): bool
    {
        if (! Schema::hasTable('leads')) {
            return false;
        }

        $external = trim((string) ($call->external_phone ?? ''));
        if ($external === '') {
            return false;
        }

        $last10 = substr(preg_replace('/\D+/', '', $external) ?? '', -10);
        if (strlen($last10) !== 10) {
            return false;
        }

        $leads = Lead::query()
            ->where('status', '!=', Lead::STATUS_SPAM)
            ->where(function ($query) use ($last10): void {
                $query->where('phone', 'like', '%'.$last10);
                if (Schema::hasColumn('leads', 'normalized_phone')) {
                    $query->orWhere('normalized_phone', 'like', '%'.$last10);
                }
            })
            ->limit(25)
            ->get(['id', 'phone', 'normalized_phone']);

        foreach ($leads as $lead) {
            $candidate = (string) ($lead->normalized_phone ?: $lead->phone);
            if ($this->callLog->phonesMatch($external, $candidate)) {
                return true;
            }
        }

        return false;
    }
}
