<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralReward;
use App\Services\ReferralPartnerService;
use App\Services\ReferralRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ReferralRewardListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'rewards' => ReferralReward::query()
                ->with(['partner', 'lead'])
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Referral rewards';
    }

    public function description(): ?string
    {
        return 'Eligible $150 rewards after Sold. Approve, mark paid, or reject.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_ADMIN];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('rewards', [
                TD::make('id', 'ID'),
                TD::make('partner', 'Partner')
                    ->render(fn (ReferralReward $r) => e($r->partner?->name.' ('.$r->partner?->code.')')),
                TD::make('lead', 'Lead')
                    ->render(function (ReferralReward $r) {
                        if (! $r->lead) {
                            return '—';
                        }

                        return Link::make('#'.$r->lead_id.' '.($r->lead->full_name ?: ''))
                            ->route('platform.leads.edit', $r->lead)
                            ->render();
                    }),
                TD::make('amount', 'Amount')
                    ->render(fn (ReferralReward $r) => e($r->amountLabel())),
                TD::make('status', 'Status')
                    ->render(fn (ReferralReward $r) => e(ReferralReward::STATUSES[$r->status] ?? $r->status)),
                TD::make('eligible_at', 'Eligible')
                    ->render(fn (ReferralReward $r) => e(optional($r->eligible_at)->format('Y-m-d') ?: '—')),
                TD::make('actions', '')
                    ->render(function (ReferralReward $r) {
                        $html = '';
                        if ($r->status === ReferralReward::STATUS_ELIGIBLE) {
                            $html .= Button::make('Approve')
                                ->type(Color::SUCCESS)
                                ->method('approve', ['reward' => $r->id])
                                ->render().' ';
                        }
                        if (in_array($r->status, [ReferralReward::STATUS_ELIGIBLE, ReferralReward::STATUS_APPROVED], true)) {
                            $html .= Button::make('Mark paid')
                                ->type(Color::PRIMARY)
                                ->method('markPaid', ['reward' => $r->id])
                                ->confirm('Mark this $150 reward as paid?')
                                ->render().' ';
                            $html .= Button::make('Reject')
                                ->type(Color::DANGER)
                                ->method('reject', ['reward' => $r->id])
                                ->render();
                        }

                        return $html !== '' ? $html : '—';
                    }),
            ]),
        ];
    }

    public function approve(Request $request, ReferralRewardService $rewards): void
    {
        $admin = Auth::user();
        abort_unless($admin !== null, 403);
        $reward = ReferralReward::query()->findOrFail((int) $request->validate([
            'reward' => ['required', 'integer', 'exists:referral_rewards,id'],
        ])['reward']);
        $rewards->approve($reward, $admin);
        Toast::success('Reward approved.');
    }

    public function markPaid(Request $request, ReferralRewardService $rewards): void
    {
        $admin = Auth::user();
        abort_unless($admin !== null, 403);
        $reward = ReferralReward::query()->findOrFail((int) $request->validate([
            'reward' => ['required', 'integer', 'exists:referral_rewards,id'],
        ])['reward']);
        $rewards->markPaid($reward, $admin);
        Toast::success('Reward marked paid.');
    }

    public function reject(Request $request, ReferralRewardService $rewards): void
    {
        $admin = Auth::user();
        abort_unless($admin !== null, 403);
        $reward = ReferralReward::query()->findOrFail((int) $request->validate([
            'reward' => ['required', 'integer', 'exists:referral_rewards,id'],
        ])['reward']);
        $rewards->reject($reward, $admin);
        Toast::info('Reward rejected.');
    }
}
