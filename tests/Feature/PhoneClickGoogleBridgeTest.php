<?php

use App\Models\PhoneClick;
use App\Models\User;
use App\Services\PhoneClickGoogleBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('a phone click is sent to every Google bridge only once', function () {
    config()->set('services.lead_bridge.urls', [
        'https://example.test/google-sheet-one',
        'https://example.test/google-sheet-two',
    ]);

    Http::fake([
        'https://example.test/*' => Http::response('ok', 200),
    ]);

    $user = User::factory()->create();
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/doors?utm_source=google',
        'landing_page' => '/doors',
        'source_label' => 'doors-landing-hero',
        'utm_source' => 'google',
        'utm_campaign' => 'doors',
        'gclid' => 'test-gclid',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_direction' => 'Inbound',
        'ringcentral_from_phone' => '+14155550999',
        'ringcentral_to_phone' => '+16504614446',
    ]);

    $bridge = app(PhoneClickGoogleBridge::class);
    $first = $bridge->sendOnce($click, (int) $user->id);
    $second = $bridge->sendOnce($click, (int) $user->id);

    expect($first['ok'])->toBeTrue()
        ->and($first['already_sent'])->toBeFalse()
        ->and($second['ok'])->toBeTrue()
        ->and($second['already_sent'])->toBeTrue();

    $click->refresh();

    expect($click->google_sheet_sent_at)->not->toBeNull()
        ->and($click->google_sheet_sent_by)->toBe($user->id);

    Http::assertSentCount(2);
    Http::assertSent(fn ($request) => $request['Form ID'] === 'Phone Click'
        && $request['Phone'] === '+14155550999'
        && $request['utm_source'] === 'google'
        && $request['gclid'] === 'test-gclid'
        && $request['idempotency_key'] === 'phone-click-'.$click->id);
});

test('google sheet phone uses the outbound RingCentral client number', function () {
    config()->set('services.lead_bridge.urls', [
        'https://example.test/google-sheet-one',
    ]);

    Http::fake([
        'https://example.test/*' => Http::response('ok', 200),
    ]);

    $user = User::factory()->create();
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_direction' => 'Outbound',
        'ringcentral_from_phone' => '+16504614446',
        'ringcentral_to_phone' => '+19255550123',
    ]);

    $result = app(PhoneClickGoogleBridge::class)->sendOnce($click, (int) $user->id);

    expect($result['ok'])->toBeTrue();
    Http::assertSent(fn ($request) => $request['Phone'] === '+19255550123');
});

test('google sheet send is blocked until a RingCentral client phone exists', function () {
    config()->set('services.lead_bridge.urls', [
        'https://example.test/google-sheet-one',
    ]);

    Http::fake();

    $user = User::factory()->create();
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    $result = app(PhoneClickGoogleBridge::class)->sendOnce($click, (int) $user->id);

    expect($result['ok'])->toBeFalse()
        ->and($result['already_sent'])->toBeFalse()
        ->and($click->refresh()->google_sheet_sent_at)->toBeNull();

    Http::assertNothingSent();
});
