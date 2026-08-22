<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralReward;
use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PartnerRewardListScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(): iterable
    {
        $partner = $this->currentPartnerOrAbort();

        return [
            'rewards' => ReferralReward::query()
                ->with('lead')
                ->where('partner_id', $partner->id)
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'My rewards';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('rewards', [
                TD::make('id', 'ID'),
                TD::make('lead', 'Lead')
                    ->render(fn (ReferralReward $r) => e($r->lead?->full_name ?: '#'.$r->lead_id)),
                TD::make('amount', 'Amount')
                    ->render(fn (ReferralReward $r) => e($r->amountLabel())),
                TD::make('status', 'Status')
                    ->render(fn (ReferralReward $r) => e(ReferralReward::STATUSES[$r->status] ?? $r->status)),
                TD::make('eligible_at', 'Eligible')
                    ->render(fn (ReferralReward $r) => e(optional($r->eligible_at)->format('Y-m-d') ?: '—')),
                TD::make('paid_at', 'Paid')
                    ->render(fn (ReferralReward $r) => e(optional($r->paid_at)->format('Y-m-d') ?: '—')),
            ]),
        ];
    }
}
