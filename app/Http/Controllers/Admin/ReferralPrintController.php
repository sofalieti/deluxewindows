<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralPartner;
use App\Services\ReferralPartnerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralPrintController extends Controller
{
    public const FORMATS = [
        'poster' => 'Poster · US Letter',
        'flyer' => 'Flyers · 2 per page',
        'cards' => 'Business cards · 10 per page',
    ];

    public function show(Request $request, string $format): View
    {
        abort_unless(array_key_exists($format, self::FORMATS), 404);

        $partner = $this->resolvePartner($request);
        $channel = $format === 'cards' ? 'card' : $format;

        return view('referrals.print', [
            'format' => $format,
            'formats' => self::FORMATS,
            'partner' => $partner,
            'first' => $partner->firstName(),
            'qrUrl' => $partner->shareUrl($channel),
            'shortUrl' => preg_replace('#^https?://#', '', $partner->referralUrl()),
            'reward' => (int) config('referral.reward_amount', 150),
            'credit' => (int) config('referral.friend_credit_amount', 150),
            'phone' => site_phone_display(),
            'adminPartnerId' => $request->filled('partner') ? $partner->id : null,
        ]);
    }

    private function resolvePartner(Request $request): ReferralPartner
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($request->filled('partner')) {
            abort_unless($user->hasAccess(ReferralPartnerService::PERMISSION_ADMIN), 403);

            return ReferralPartner::query()->findOrFail((int) $request->query('partner'));
        }

        abort_unless($user->hasAccess(ReferralPartnerService::PERMISSION_PORTAL), 403);

        $partner = $user->referralPartner;
        abort_unless($partner instanceof ReferralPartner, 403, 'No referral partner profile linked to this account.');

        return $partner;
    }
}
