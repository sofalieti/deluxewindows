<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\ReferralReward;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ReferralRewardService
{
    /**
     * When a referred lead reaches Sold, create an eligible reward (not paid).
     */
    public function syncEligibleForLead(Lead $lead): ?ReferralReward
    {
        if ($lead->status !== Lead::STATUS_SOLD) {
            return null;
        }

        if ((int) ($lead->referral_partner_id ?? 0) <= 0) {
            return null;
        }

        $existing = ReferralReward::query()->where('lead_id', $lead->id)->first();
        if ($existing !== null) {
            return $existing;
        }

        try {
            return ReferralReward::query()->create([
                'partner_id' => (int) $lead->referral_partner_id,
                'lead_id' => $lead->id,
                'amount_cents' => ReferralReward::DEFAULT_AMOUNT_CENTS,
                'status' => ReferralReward::STATUS_ELIGIBLE,
                'eligible_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Referral reward create failed', [
                'lead_id' => $lead->id,
                'error' => $exception->getMessage(),
            ]);

            return ReferralReward::query()->where('lead_id', $lead->id)->first();
        }
    }

    public function approve(ReferralReward $reward, User $admin, ?string $note = null): ReferralReward
    {
        if (! in_array($reward->status, [ReferralReward::STATUS_ELIGIBLE, ReferralReward::STATUS_REJECTED], true)) {
            return $reward;
        }

        $reward->forceFill([
            'status' => ReferralReward::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'rejected_at' => null,
            'admin_note' => $note ?? $reward->admin_note,
        ])->save();

        return $reward->refresh();
    }

    public function markPaid(ReferralReward $reward, User $admin, ?string $note = null): ReferralReward
    {
        if (! in_array($reward->status, [ReferralReward::STATUS_APPROVED, ReferralReward::STATUS_ELIGIBLE], true)) {
            return $reward;
        }

        $reward->forceFill([
            'status' => ReferralReward::STATUS_PAID,
            'approved_at' => $reward->approved_at ?? now(),
            'approved_by' => $reward->approved_by ?? $admin->id,
            'paid_at' => now(),
            'paid_by' => $admin->id,
            'admin_note' => $note ?? $reward->admin_note,
        ])->save();

        return $reward->refresh();
    }

    public function reject(ReferralReward $reward, User $admin, ?string $note = null): ReferralReward
    {
        if ($reward->status === ReferralReward::STATUS_PAID) {
            return $reward;
        }

        $reward->forceFill([
            'status' => ReferralReward::STATUS_REJECTED,
            'rejected_at' => now(),
            'admin_note' => $note ?? $reward->admin_note,
            'approved_by' => $admin->id,
        ])->save();

        return $reward->refresh();
    }
}
