<?php

use App\Mail\SiteCallDigestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

test('new lead emails always include felix', function () {
    expect(config('services.lead_notifications.to'))
        ->toContain('felix@deluxewindows.com')
        ->toContain('sofalieti@gmail.com');
});

test('the evening digest emails matched site calls with the caller number and summary', function () {
    Mail::fake();
    PhoneClick::query()->delete();
    RingCentralCall::query()->delete();
    config()->set('services.lead_notifications.to', [
        'info@deluxewindows.com',
        'felix@deluxewindows.com',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-10-22 20:00:00', 'America/Los_Angeles'));

    $call = RingCentralCall::query()->create([
        'ringcentral_call_id' => 'digest-call-1',
        'direction' => 'Inbound',
        'started_at' => Carbon::parse('2026-10-22 15:10:00', 'America/Los_Angeles'),
        'duration' => 95,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505551212',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505551212',
        'result' => 'Accepted',
        'transcript_status' => RingCentralCall::TRANSCRIPT_COMPLETED,
        'transcript' => 'Caller asked about replacing eight windows in San Mateo.',
        'transcript_summary' => [
            'overview' => 'Caller wants a quote for eight windows in San Mateo.',
        ],
        'synced_at' => Carbon::parse('2026-10-22 15:12:00', 'America/Los_Angeles'),
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/windows',
        'source_label' => 'hero-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_id' => 'digest-call-1',
        'ringcentral_direction' => 'Inbound',
        'ringcentral_from_phone' => '+16505551212',
        'ringcentral_to_phone' => '+16504614446',
        'ringcentral_result' => 'Accepted',
        'ringcentral_duration' => 95,
        'ringcentral_call_started_at' => Carbon::parse('2026-10-22 15:10:00', 'America/Los_Angeles'),
        'created_at' => Carbon::parse('2026-10-22 15:09:00', 'America/Los_Angeles'),
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/',
        'source_label' => 'header-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_NO_CALL,
        'created_at' => Carbon::parse('2026-10-22 11:00:00', 'America/Los_Angeles'),
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/doors',
        'source_label' => 'hero-phone',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_id' => 'digest-call-missed',
        'ringcentral_from_phone' => '+16505559999',
        'ringcentral_result' => 'Missed',
        'ringcentral_duration' => 0,
        'ringcentral_call_started_at' => Carbon::parse('2026-10-22 16:00:00', 'America/Los_Angeles'),
        'created_at' => Carbon::parse('2026-10-22 15:59:00', 'America/Los_Angeles'),
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'is_spam' => true,
        'created_at' => Carbon::parse('2026-10-22 12:00:00', 'America/Los_Angeles'),
    ]);

    $older = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_NO_CALL,
    ]);
    PhoneClick::query()->whereKey($older->id)->update([
        'created_at' => Carbon::parse('2026-10-20 12:00:00', 'America/Los_Angeles'),
    ]);

    try {
        Artisan::call('calls:site-digest');

        Mail::assertSent(SiteCallDigestMail::class, function (SiteCallDigestMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('info@deluxewindows.com')
                && $mail->hasTo('felix@deluxewindows.com')
                && $mail->clicks->count() === 1
                && str_contains($html, '+1 (650) 555-1212')
                && str_contains($html, 'Caller wants a quote for eight windows in San Mateo.')
                && str_contains($html, 'Only calls where someone got through')
                && ! str_contains($html, '+1 (650) 555-9999')
                && ! str_contains($html, 'RingCentral did not find a call');
        });
    } finally {
        Carbon::setTestNow();
    }
});

test('the evening digest stays quiet when nobody called', function () {
    Mail::fake();
    PhoneClick::query()->delete();
    config()->set('services.lead_notifications.to', ['felix@deluxewindows.com']);
    Carbon::setTestNow(Carbon::parse('2026-10-22 20:00:00', 'America/Los_Angeles'));

    try {
        Artisan::call('calls:site-digest');

        Mail::assertNothingSent();
    } finally {
        Carbon::setTestNow();
    }
});
