<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PhoneClickController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $payload = [
            'phone' => trim((string) $request->input('phone')),
            'page_url' => trim((string) (
                $request->input('page_url')
                ?: $request->headers->get('referer', '')
            )),
            'landing_page' => trim((string) $request->input('landing_page')),
            'first_landing_page' => trim((string) $request->input('first_landing_page')),
            'referrer' => trim((string) $request->input('referrer')),
            'first_referrer' => trim((string) $request->input('first_referrer')),
            'source_label' => trim((string) $request->input('source_label')),
            'geo_location' => trim((string) $request->input('geo_location')),
            'utm_source' => trim((string) $request->input('utm_source')),
            'utm_medium' => trim((string) $request->input('utm_medium')),
            'utm_campaign' => trim((string) $request->input('utm_campaign')),
            'utm_content' => trim((string) $request->input('utm_content')),
            'utm_term' => trim((string) $request->input('utm_term')),
            'utm_city' => trim((string) $request->input('utm_city')),
            'utm_redirect' => trim((string) $request->input('utm_redirect')),
            'first_utm_source' => trim((string) $request->input('first_utm_source')),
            'first_utm_medium' => trim((string) $request->input('first_utm_medium')),
            'first_utm_campaign' => trim((string) $request->input('first_utm_campaign')),
            'first_utm_content' => trim((string) $request->input('first_utm_content')),
            'first_utm_term' => trim((string) $request->input('first_utm_term')),
            'first_utm_city' => trim((string) $request->input('first_utm_city')),
            'matchtype' => trim((string) $request->input('matchtype')),
            'device' => trim((string) $request->input('device')),
            'creative' => trim((string) $request->input('creative')),
            'gclid' => trim((string) $request->input('gclid')),
            'fbclid' => trim((string) $request->input('fbclid')),
            'msclkid' => trim((string) $request->input('msclkid')),
            'first_gclid' => trim((string) $request->input('first_gclid')),
            'first_fbclid' => trim((string) $request->input('first_fbclid')),
            'first_msclkid' => trim((string) $request->input('first_msclkid')),
        ];

        $validated = validator($payload, [
            'phone' => 'nullable|string|max:50',
            'page_url' => 'nullable|string|max:1000',
            'landing_page' => 'nullable|string|max:1000',
            'first_landing_page' => 'nullable|string|max:1000',
            'referrer' => 'nullable|string|max:1000',
            'first_referrer' => 'nullable|string|max:1000',
            'source_label' => 'nullable|string|max:255',
            'geo_location' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_city' => 'nullable|string|max:255',
            'utm_redirect' => 'nullable|string|max:255',
            'first_utm_source' => 'nullable|string|max:255',
            'first_utm_medium' => 'nullable|string|max:255',
            'first_utm_campaign' => 'nullable|string|max:255',
            'first_utm_content' => 'nullable|string|max:255',
            'first_utm_term' => 'nullable|string|max:255',
            'first_utm_city' => 'nullable|string|max:255',
            'matchtype' => 'nullable|string|max:255',
            'device' => 'nullable|string|max:255',
            'creative' => 'nullable|string|max:255',
            'gclid' => 'nullable|string|max:255',
            'fbclid' => 'nullable|string|max:255',
            'msclkid' => 'nullable|string|max:255',
            'first_gclid' => 'nullable|string|max:255',
            'first_fbclid' => 'nullable|string|max:255',
            'first_msclkid' => 'nullable|string|max:255',
        ])->validate();

        try {
            $click = PhoneClick::query()->create([
                ...$validated,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
                'meta' => [
                    'via' => 'site-tel-click',
                    'request_id' => (string) $request->headers->get('x-request-id', ''),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Phone click save failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => false], 500);
        }

        try {
            MatchPhoneClickToRingCentral::dispatch($click->id)
                ->delay(now()->addMinutes(3))
                ->afterCommit();
        } catch (\Throwable $e) {
            Log::warning('RingCentral phone click job dispatch failed', [
                'phone_click_id' => $click->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
