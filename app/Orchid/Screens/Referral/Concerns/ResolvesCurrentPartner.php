<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Referral\Concerns;

use App\Models\ReferralPartner;
use Illuminate\Support\Facades\Auth;

trait ResolvesCurrentPartner
{
    protected function currentPartnerOrAbort(): ReferralPartner
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $partner = $user->referralPartner;
        abort_unless($partner instanceof ReferralPartner, 403, 'No referral partner profile linked to this account.');

        return $partner;
    }
}
