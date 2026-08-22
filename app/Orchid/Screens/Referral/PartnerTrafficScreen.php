<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Models\PhoneClick;
use App\Models\SiteVisit;
use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class PartnerTrafficScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(): iterable
    {
        $partner = $this->currentPartnerOrAbort();

        return [
            'visits' => SiteVisit::query()
                ->where('referral_partner_id', $partner->id)
                ->defaultSort('id', 'desc')
                ->paginate(25, ['*'], 'visits_page'),
            'clicks' => PhoneClick::query()
                ->where('referral_partner_id', $partner->id)
                ->defaultSort('id', 'desc')
                ->paginate(25, ['*'], 'clicks_page'),
        ];
    }

    public function name(): ?string
    {
        return 'My traffic';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function layout(): iterable
    {
        return [
            Layout::tabs([
                'Visits' => Layout::table('visits', [
                    TD::make('created_at', 'When')
                        ->render(fn (SiteVisit $v) => e(optional($v->created_at)->format('Y-m-d H:i'))),
                    TD::make('page_url', 'Page')
                        ->render(fn (SiteVisit $v) => e(\Illuminate\Support\Str::limit((string) $v->page_url, 70))),
                    TD::make('device', 'Device'),
                    TD::make('utm_campaign', 'Campaign'),
                ]),
                'Phone clicks' => Layout::table('clicks', [
                    TD::make('created_at', 'When')
                        ->render(fn (PhoneClick $c) => e(optional($c->created_at)->format('Y-m-d H:i'))),
                    TD::make('page_url', 'Page')
                        ->render(fn (PhoneClick $c) => e(\Illuminate\Support\Str::limit((string) $c->page_url, 70))),
                    TD::make('source_label', 'Source'),
                    TD::make('ringcentral_status', 'RC'),
                ]),
            ]),
        ];
    }
}
