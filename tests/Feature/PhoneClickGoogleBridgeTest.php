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
        && $request['Phone'] === '+16504614446'
        && $request['utm_source'] === 'google'
        && $request['gclid'] === 'test-gclid'
        && $request['idempotency_key'] === 'phone-click-'.$click->id);
});
