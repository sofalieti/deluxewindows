<?php

declare(strict_types=1);

use App\Models\PhoneClick;
use App\Models\User;
use App\Services\Ads\OfflineConversionStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

test('offline conversion stats count bing uploads and last sent time', function () {
    CarbonImmutable::setTestNow('2026-08-10 18:00:00 UTC');

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'ms-sent',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'bing_ads_conversion_sent_at' => CarbonImmutable::parse('2026-08-09T20:00:00Z'),
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'ms-waiting',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'ms-failed',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'bing_ads_conversion_error' => 'OfflineConversionNameInvalid',
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'gclid' => 'g-sent',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'google_ads_conversion_sent_at' => CarbonImmutable::parse('2026-08-08T12:00:00Z'),
    ]);
    // No click id — excluded
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
    ]);

    $stats = app(OfflineConversionStatsService::class)->summary();

    expect($stats['bing']['uploaded'])->toBe(1)
        ->and($stats['bing']['waiting'])->toBe(2)
        ->and($stats['bing']['failed'])->toBe(1)
        ->and($stats['bing']['last_sent_label'])->toContain('Aug 9, 2026')
        ->and($stats['google']['uploaded'])->toBe(1)
        ->and($stats['google']['waiting'])->toBe(0)
        ->and($stats['google']['last_sent_label'])->toContain('Aug 8, 2026');

    CarbonImmutable::setTestNow();
});

test('phone clicks screen shows offline conversion stats', function () {
    $user = User::factory()->create([
        'permissions' => array_merge(adminTrafficPermissions(leads: false, phoneClicks: true), [
            'platform.index' => true,
        ]),
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'ms-ui',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'bing_ads_conversion_sent_at' => now()->subHour(),
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks'))
        ->assertOk()
        ->assertSee('Offline conversions')
        ->assertSee('Microsoft Ads (Bing)')
        ->assertSee('Last sent:');
});
