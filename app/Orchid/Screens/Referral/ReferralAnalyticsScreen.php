<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Services\ReferralAnalyticsService;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ReferralAnalyticsScreen extends Screen
{
    public function query(ReferralAnalyticsService $analytics): iterable
    {
        $metrics = $analytics->adminMetrics();

        return [
            'metrics' => [
                'partners_active' => (string) $metrics['partners_active'],
                'visits' => (string) $metrics['visits'],
                'phone_clicks' => (string) $metrics['phone_clicks'],
                'leads' => (string) $metrics['leads'],
                'sold' => (string) $metrics['sold'],
                'lead_rate' => $metrics['lead_rate'].'%',
                'liability' => (string) $metrics['liability'],
                'paid_ytd' => (string) $metrics['paid_ytd'],
            ],
            'topPartners' => $analytics->topPartners(),
        ];
    }

    public function name(): ?string
    {
        return 'Referral analytics';
    }

    public function description(): ?string
    {
        return 'Site-wide referral KPIs for the last 30 days (except paid YTD).';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_ADMIN];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Active partners' => 'metrics.partners_active',
                'Visits (30d)' => 'metrics.visits',
                'Phone clicks (30d)' => 'metrics.phone_clicks',
                'Leads (30d)' => 'metrics.leads',
                'Sold (30d)' => 'metrics.sold',
                'Lead rate' => 'metrics.lead_rate',
                'Payout liability' => 'metrics.liability',
                'Paid YTD' => 'metrics.paid_ytd',
            ]),

            Layout::table('topPartners', [
                TD::make('code', 'Code'),
                TD::make('name', 'Partner'),
                TD::make('visits_count', 'Visits'),
                TD::make('leads_count', 'Leads'),
                TD::make('rewards_count', 'Rewards'),
                TD::make('status', 'Status'),
            ])->title('Top partners by leads'),
        ];
    }
}
