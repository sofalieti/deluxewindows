<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral;

use App\Orchid\Screens\Referral\Concerns\ResolvesCurrentPartner;
use App\Services\ReferralPartnerService;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;

class PartnerLinkScreen extends Screen
{
    use ResolvesCurrentPartner;

    public function query(): iterable
    {
        $partner = $this->currentPartnerOrAbort();

        return [
            'partner' => $partner,
        ];
    }

    public function name(): ?string
    {
        return 'My referral link';
    }

    public function permission(): ?iterable
    {
        return [ReferralPartnerService::PERMISSION_PORTAL];
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('partner', [
                Sight::make('code', 'Partner code'),
                Sight::make('referral_url', 'Short link')
                    ->render(fn ($partner) => '<code>'.e($partner->referralUrl()).'</code>'),
                Sight::make('campaign_url', 'Full UTM link')
                    ->render(fn ($partner) => '<code style="word-break:break-all">'.e($partner->campaignUrl()).'</code>'),
                Sight::make('open', '')
                    ->render(fn ($partner) => Link::make('Open short link')
                        ->href($partner->referralUrl())
                        ->target('_blank')
                        ->render()),
            ]),
        ];
    }
}
