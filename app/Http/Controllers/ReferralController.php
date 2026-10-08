<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReferralApplication;
use App\Models\ReferralPartner;
use App\Services\LeadSpamGuard;
use App\Services\Seo\OrganizationSchema;
use App\Services\Seo\PageMetadata;
use App\Services\YelpReviewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function landing(): View
    {
        return view('referrals.landing', $this->offer());
    }

    public function apply(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:5000'],
        ]);

        $spam = app(LeadSpamGuard::class)->inspect([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'phone' => (string) ($validated['phone'] ?? ''),
            'city' => '',
            'message' => (string) ($validated['message'] ?? ''),
        ]);

        ReferralApplication::query()->create([
            'full_name' => $validated['full_name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => ReferralApplication::STATUS_PENDING,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => [
                'via' => 'referral-landing',
                'spam' => (bool) ($spam['spam'] ?? false),
                'spam_reason' => $spam['reason'] ?? null,
            ],
        ]);

        return redirect()
            ->to(url('/referrals').'#apply')
            ->with('referral_application_success', true);
    }

    /**
     * Short link /r/{code}[?via=channel]. Active partners land on their personal
     * invite page; the channel becomes utm_medium so posters, QR codes and
     * posts are tracked separately.
     */
    public function redirect(Request $request, string $code): RedirectResponse
    {
        $partner = $this->activePartner($code);

        if ($partner === null) {
            $campaign = strtolower(trim($code));

            return redirect()->to(
                url('/?utm_source=referral&utm_medium=partner&utm_campaign='.rawurlencode($campaign))
            );
        }

        return redirect()->to($partner->inviteUrl((string) $request->query('via', 'partner')));
    }

    public function invite(string $code): View|RedirectResponse
    {
        $partner = $this->activePartner($code);
        if ($partner === null) {
            return redirect()->to('/');
        }

        $offer = $this->offer();
        $title = $partner->firstName().' sent you $'.$offer['friendCredit'].' off new windows';
        $description = 'Your neighbor recommends Deluxe Windows. Book a free in-home estimate and get $'
            .$offer['friendCredit'].' off your Bay Area window or door project.';
        $baseUrl = rtrim((string) config('services.sitemap.base_url', 'https://www.deluxewindows.com'), '/');
        $image = $baseUrl.'/webflow-assets/images/new-construction/after-with-windows.avif';

        // Personal pages stay out of the index; the canonical points at the program page.
        $metadata = new PageMetadata(
            key: 'referral-invite',
            path: '/invite/'.$partner->code,
            title: $title.' | Deluxe Windows',
            description: $description,
            canonical: $baseUrl.'/referrals',
            ogTitle: $title,
            ogDescription: $description,
            ogImage: $image,
            ogType: 'website',
            robots: 'noindex,follow',
            twitterTitle: $title,
            twitterDescription: $description,
            twitterImage: $image,
            twitterCard: 'summary_large_image',
            h1: $title,
            h1Subline: '',
            faq: [],
            schema: ['primary_type' => 'WebPage'],
        );

        return view('referrals.invite', $offer + [
            'partner' => $partner,
            'pageMetadata' => $metadata,
            'pageSchemas' => [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function offer(): array
    {
        try {
            $yelp = app(YelpReviewsService::class)->payload();
        } catch (\Throwable) {
            $yelp = [];
        }

        return [
            'reward' => (int) config('referral.reward_amount', 150),
            'friendCredit' => (int) config('referral.friend_credit_amount', 150),
            'payoutMethods' => array_values((array) config('referral.payout_methods', ['Zelle', 'Venmo', 'Check'])),
            'payoutWindow' => (string) config('referral.payout_window', 'within 14 days'),
            'yelpRating' => (string) ($yelp['business']['rating_label'] ?? '4.5'),
            'yelpCount' => (string) ($yelp['business']['reviews_label'] ?? '250+'),
            'license' => 'CA Lic. #'.OrganizationSchema::CSLB_LICENSE,
        ];
    }

    private function activePartner(string $code): ?ReferralPartner
    {
        return ReferralPartner::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->where('status', ReferralPartner::STATUS_ACTIVE)
            ->first();
    }
}
