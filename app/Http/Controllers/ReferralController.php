<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReferralApplication;
use App\Models\ReferralPartner;
use App\Services\LeadSpamGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function landing(): View
    {
        return view('referrals.landing');
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

    public function redirect(string $code): RedirectResponse
    {
        $normalized = strtolower(trim($code));
        $partner = ReferralPartner::query()
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->where('status', ReferralPartner::STATUS_ACTIVE)
            ->first();

        $campaign = $partner?->code ?? $normalized;

        return redirect()->to(
            url('/?utm_source=referral&utm_medium=partner&utm_campaign='.rawurlencode($campaign))
        );
    }
}
