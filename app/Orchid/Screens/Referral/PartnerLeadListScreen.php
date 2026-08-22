<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\Lead;
use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PartnerLeadListScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(): iterable
    {
        $partner = $this->currentPartnerOrAbort();

        return [
            'leads' => Lead::query()
                ->where('referral_partner_id', $partner->id)
                ->where('status', '!=', Lead::STATUS_SPAM)
                ->defaultSort('id', 'desc')
                ->paginate(50),
        ];
    }

    public function name(): ?string
    {
        return 'My referred leads';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('leads', [
                TD::make('created_at', 'Date')
                    ->render(fn (Lead $lead) => e(optional($lead->created_at)->format('Y-m-d H:i'))),
                TD::make('full_name', 'Name'),
                TD::make('phone', 'Phone'),
                TD::make('email', 'Email'),
                TD::make('city', 'City'),
                TD::make('status', 'Status')
                    ->render(fn (Lead $lead) => e($lead->statusLabel())),
                TD::make('page_url', 'Page')
                    ->render(fn (Lead $lead) => e(\Illuminate\Support\Str::limit((string) $lead->page_url, 60))),
            ]),
        ];
    }
}
