<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\LeadNotificationMail;
use App\Models\Lead;
use App\Services\ContactFromLeadService;
use App\Services\LeadSpamGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Phone-only contact requests from the Bing Ads phone-choice modal.
 * Channels: callback (we'll call), sms / whatsapp (we'll text them).
 */
class CallbackRequestController extends Controller
{
    private const CHANNELS = [
        'callback' => [
            'full_name' => 'Callback request',
            'message' => 'Bing Ads phone modal callback request',
            'form_id' => 'bing-phone-callback',
            'via' => 'bing-phone-callback',
            'email_prefix' => 'callback',
        ],
        'sms' => [
            'full_name' => 'SMS request',
            'message' => 'Bing Ads phone modal SMS request — please text this number',
            'form_id' => 'bing-phone-sms',
            'via' => 'bing-phone-sms',
            'email_prefix' => 'sms',
        ],
        'whatsapp' => [
            'full_name' => 'WhatsApp request',
            'message' => 'Bing Ads phone modal WhatsApp request — please message this number',
            'form_id' => 'bing-phone-whatsapp',
            'via' => 'bing-phone-whatsapp',
            'email_prefix' => 'whatsapp',
        ],
    ];

    public function store(Request $request): JsonResponse
    {
        $payload = [
            'phone' => trim((string) ($request->input('phone') ?: $request->input('Phone'))),
            'channel' => strtolower(trim((string) $request->input('channel', 'callback'))),
            'page_url' => trim((string) (
                $request->input('page_url')
                ?: $request->input('Page')
                ?: $request->headers->get('referer', '')
            )),
            'landing_page' => trim((string) $request->input('landing_page')),
            'referrer' => trim((string) $request->input('referrer')),
            'geo_location' => trim((string) $request->input('geo_location')),
            'utm_source' => trim((string) $request->input('utm_source')),
            'utm_medium' => trim((string) $request->input('utm_medium')),
            'utm_campaign' => trim((string) $request->input('utm_campaign')),
            'utm_content' => trim((string) $request->input('utm_content')),
            'utm_term' => trim((string) $request->input('utm_term')),
            'utm_city' => trim((string) $request->input('utm_city')),
            'utm_redirect' => trim((string) $request->input('utm_redirect')),
            'matchtype' => trim((string) $request->input('matchtype')),
            'device' => trim((string) $request->input('device')),
            'creative' => trim((string) ($request->input('creative') ?: $request->input('utm_creative'))),
            'gclid' => trim((string) $request->input('gclid')),
            'fbclid' => trim((string) $request->input('fbclid')),
            'msclkid' => trim((string) $request->input('msclkid')),
        ];

        $validator = Validator::make($payload, [
            'phone' => 'required|string|max:50',
            'channel' => 'required|in:callback,sms,whatsapp',
            'page_url' => 'nullable|string|max:1000',
            'landing_page' => 'nullable|string|max:1000',
            'referrer' => 'nullable|string|max:1000',
            'geo_location' => 'nullable|string|max:255',
            'utm_source' => 'nullable|string|max:255',
            'utm_medium' => 'nullable|string|max:255',
            'utm_campaign' => 'nullable|string|max:255',
            'utm_content' => 'nullable|string|max:255',
            'utm_term' => 'nullable|string|max:255',
            'utm_city' => 'nullable|string|max:255',
            'utm_redirect' => 'nullable|string|max:255',
            'matchtype' => 'nullable|string|max:255',
            'device' => 'nullable|string|max:255',
            'creative' => 'nullable|string|max:255',
            'gclid' => 'nullable|string|max:255',
            'fbclid' => 'nullable|string|max:255',
            'msclkid' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();
        $channel = $validated['channel'];
        $channelMeta = self::CHANNELS[$channel];
        $phone = $validated['phone'];
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;
        if ($last10 === '') {
            $last10 = (string) time();
        }

        $fullName = $channelMeta['full_name'];
        $email = $channelMeta['email_prefix'].'+'.$last10.'@noreply.deluxewindows.com';
        $message = $channelMeta['message'];
        $formId = $channelMeta['form_id'];
        $via = $channelMeta['via'];

        $spam = app(LeadSpamGuard::class)->inspect([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'city' => '',
            'message' => $message,
        ]);

        $pageUrl = $validated['page_url'] !== ''
            ? $validated['page_url']
            : trim((string) $request->headers->get('referer', ''));

        $firstTouch = array_filter([
            'landing_page' => trim((string) $request->input('first_landing_page')),
            'referrer' => trim((string) $request->input('first_referrer')),
            'utm_source' => trim((string) $request->input('first_utm_source')),
            'utm_medium' => trim((string) $request->input('first_utm_medium')),
            'utm_campaign' => trim((string) $request->input('first_utm_campaign')),
            'utm_content' => trim((string) $request->input('first_utm_content')),
            'utm_term' => trim((string) $request->input('first_utm_term')),
            'utm_city' => trim((string) $request->input('first_utm_city')),
            'gclid' => trim((string) $request->input('first_gclid')),
            'fbclid' => trim((string) $request->input('first_fbclid')),
            'msclkid' => trim((string) $request->input('first_msclkid')),
        ], static fn (string $value): bool => $value !== '');

        $meta = [
            'request_id' => (string) $request->headers->get('x-request-id', ''),
            'via' => $via,
            'channel' => $channel,
            'geo_location' => $validated['geo_location'],
            'landing_page' => $validated['landing_page'],
            'referrer' => $validated['referrer'],
            'utm_content' => $validated['utm_content'],
            'utm_term' => $validated['utm_term'],
            'utm_city' => $validated['utm_city'],
            'utm_redirect' => $validated['utm_redirect'],
            'matchtype' => $validated['matchtype'],
            'device' => $validated['device'],
            'creative' => $validated['creative'],
            'gclid' => $validated['gclid'],
            'fbclid' => $validated['fbclid'],
            'msclkid' => $validated['msclkid'],
            'form_id' => $formId,
            'first_touch' => $firstTouch,
        ];

        if ($spam['spam']) {
            $meta['spam_reason'] = (string) ($spam['reason'] ?? 'unknown');
            $meta['spam_flagged_at'] = now()->toIso8601String();

            $spamLead = Lead::query()->create([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'city' => null,
                'message' => $message,
                'page_url' => $pageUrl,
                'utm_source' => $validated['utm_source'],
                'utm_medium' => $validated['utm_medium'],
                'utm_campaign' => $validated['utm_campaign'],
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'status' => Lead::STATUS_SPAM,
                'meta' => $meta,
            ]);

            Log::info('Bing phone modal lead saved as spam', [
                'lead_id' => $spamLead->id,
                'channel' => $channel,
                'reason' => $spam['reason'],
                'phone' => $phone,
                'ip' => $request->ip(),
            ]);

            return response()->json(['ok' => true, 'spam' => true]);
        }

        $lead = Lead::query()->create([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'city' => null,
            'message' => $message,
            'page_url' => $pageUrl,
            'utm_source' => $validated['utm_source'],
            'utm_medium' => $validated['utm_medium'],
            'utm_campaign' => $validated['utm_campaign'],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta' => $meta,
        ]);

        app(\App\Services\ReferralAttributionService::class)->attributeLead($lead);

        app(ContactFromLeadService::class)->attachNewLead($lead);

        $notifyRecipients = (array) config('services.lead_notifications.to', []);
        if ($notifyRecipients !== []) {
            try {
                Mail::to($notifyRecipients)->send(new LeadNotificationMail($lead));
            } catch (\Throwable $e) {
                Log::warning('Bing phone modal lead notification email failed', [
                    'lead_id' => $lead->id,
                    'channel' => $channel,
                    'phone' => $phone,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'ok' => true,
            'spam' => false,
            'lead_id' => $lead->id,
            'channel' => $channel,
        ]);
    }
}
