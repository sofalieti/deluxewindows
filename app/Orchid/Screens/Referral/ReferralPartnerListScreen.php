<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\ReferralPartner;
use App\Services\ReferralPartnerService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ReferralPartnerListScreen extends Screen
{
    public function query(): iterable
    {
        return [
            'partners' => ReferralPartner::query()
                ->with('user')
                ->withCount(['leads', 'visits', 'rewards'])
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'Referral partners';
    }

    public function description(): ?string
    {
        return 'Active and paused partners with referral codes and traffic counts.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_ADMIN];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('partners', [
                TD::make('code', 'Code')
                    ->render(fn (ReferralPartner $p) => '<code>'.e($p->code).'</code>'),
                TD::make('name', 'Name'),
                TD::make('email', 'Email'),
                TD::make('status', 'Status')
                    ->render(fn (ReferralPartner $p) => e(ReferralPartner::STATUSES[$p->status] ?? $p->status)),
                TD::make('visits_count', 'Visits'),
                TD::make('leads_count', 'Leads'),
                TD::make('rewards_count', 'Rewards'),
                TD::make('link', 'Link')
                    ->render(fn (ReferralPartner $p) => Link::make('Open')
                        ->href($p->referralUrl())
                        ->target('_blank')
                        ->render()),
                TD::make('actions', '')
                    ->render(function (ReferralPartner $p) {
                        if ($p->status === ReferralPartner::STATUS_ACTIVE) {
                            return Button::make('Pause')
                                ->type(Color::WARNING)
                                ->method('pause', ['partner' => $p->id])
                                ->render();
                        }

                        if (in_array($p->status, [ReferralPartner::STATUS_PAUSED, ReferralPartner::STATUS_PENDING], true)) {
                            return Button::make('Activate')
                                ->type(Color::SUCCESS)
                                ->method('activate', ['partner' => $p->id])
                                ->render();
                        }

                        return '—';
                    }),
            ]),
        ];
    }

    public function pause(Request $request): void
    {
        $partner = ReferralPartner::query()->findOrFail((int) $request->validate([
            'partner' => ['required', 'integer', 'exists:referral_partners,id'],
        ])['partner']);
        $partner->forceFill(['status' => ReferralPartner::STATUS_PAUSED])->save();
        Toast::info('Partner paused.');
    }

    public function activate(Request $request): void
    {
        $partner = ReferralPartner::query()->findOrFail((int) $request->validate([
            'partner' => ['required', 'integer', 'exists:referral_partners,id'],
        ])['partner']);
        $partner->forceFill(['status' => ReferralPartner::STATUS_ACTIVE])->save();
        Toast::success('Partner activated.');
    }
}
