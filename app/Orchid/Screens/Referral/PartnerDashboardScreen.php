<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralAnalyticsService;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class PartnerDashboardScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(ReferralAnalyticsService $analytics): iterable
    {
        $partner = $this->currentPartnerOrAbort();
        $metrics = $analytics->partnerMetrics($partner);

        return [
            'partner' => $partner,
            'metrics' => [
                'visits' => (string) $metrics['visits'],
                'phone_clicks' => (string) $metrics['phone_clicks'],
                'leads' => (string) $metrics['leads'],
                'sold' => (string) $metrics['sold'],
                'new' => (string) $metrics['new'],
                'quoted' => (string) $metrics['quoted'],
                'estimated' => (string) $metrics['estimated'],
                'paid' => (string) $metrics['paid'],
            ],
            'link' => $partner->referralUrl(),
        ];
    }

    public function name(): ?string
    {
        return 'My referral dashboard';
    }

    public function description(): ?string
    {
        return 'Visits, leads, and earnings for your referral link.';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Copy my link page')
                ->icon('bs.link-45deg')
                ->route('platform.referral.my-link'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Visits' => 'metrics.visits',
                'Phone clicks' => 'metrics.phone_clicks',
                'Leads' => 'metrics.leads',
                'New' => 'metrics.new',
                'Quoted' => 'metrics.quoted',
                'Sold' => 'metrics.sold',
                'Estimated' => 'metrics.estimated',
                'Paid' => 'metrics.paid',
            ]),
            Layout::view('admin.referral.partner-link-card'),
        ];
    }
}
