<?php

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Services\TrafficSourceVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

/**
 * @return array<string, bool>
 */
function trafficPermissions(string $section, array $buckets, bool $withSection = true): array
{
    $permissions = [];

    if ($withSection) {
        $permissions[$section === TrafficSourceVisibility::SECTION_LEADS ? 'platform.leads' : 'platform.phone-clicks'] = true;
    }

    foreach ($buckets as $bucket) {
        $permissions[TrafficSourceVisibility::permission($section, $bucket)] = true;
    }

    return $permissions;
}

function userWithTrafficPermissions(array $permissions): User
{
    $user = User::factory()->create();
    $user->forceFill(['permissions' => $permissions])->save();

    return $user;
}

test('leads list only shows sources the role may see', function () {
    Lead::query()->create([
        'full_name' => 'Google Lead',
        'email' => 'google@example.com',
        'phone' => '5551111111',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'meta' => ['gclid' => 'gclid-1'],
    ]);
    Lead::query()->create([
        'full_name' => 'Bing Lead',
        'email' => 'bing@example.com',
        'phone' => '5552222222',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'meta' => ['msclkid' => 'ms-1'],
    ]);
    Lead::query()->create([
        'full_name' => 'Direct Lead',
        'email' => 'direct@example.com',
        'phone' => '5553333333',
    ]);

    $user = userWithTrafficPermissions(trafficPermissions(
        TrafficSourceVisibility::SECTION_LEADS,
        ['adwords', 'direct']
    ));

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.leads'))
        ->assertOk()
        ->assertSee('Google Lead')
        ->assertSee('Direct Lead')
        ->assertDontSee('Bing Lead')
        ->assertSee('Visible sources: AdWords / Google Ads, Direct');
});

test('a lead from a hidden source returns 403 on its edit screen', function () {
    $lead = Lead::query()->create([
        'full_name' => 'Hidden Bing',
        'email' => 'hidden@example.com',
        'phone' => '5554444444',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'meta' => ['msclkid' => 'ms-hidden'],
    ]);

    $user = userWithTrafficPermissions(trafficPermissions(
        TrafficSourceVisibility::SECTION_LEADS,
        ['seo']
    ));

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.leads.edit', $lead))
        ->assertForbidden();
});

test('phone clicks list is filtered independently from leads', function () {
    PhoneClick::query()->create([
        'phone' => '+14155550101',
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'google',
        'utm_medium' => 'organic',
    ]);
    PhoneClick::query()->create([
        'phone' => '+14155550102',
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'msclkid' => 'ms-click',
    ]);

    $user = userWithTrafficPermissions(array_merge(
        trafficPermissions(TrafficSourceVisibility::SECTION_PHONE_CLICKS, ['seo']),
        trafficPermissions(TrafficSourceVisibility::SECTION_LEADS, ['bing']),
    ));

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks'))
        ->assertOk()
        ->assertSee('+14155550101')
        ->assertDontSee('+14155550102');
});

test('traffic_source is stored from attribution on save', function () {
    $lead = Lead::query()->create([
        'full_name' => 'SEO Person',
        'email' => 'seo@example.com',
        'phone' => '5555555555',
        'utm_source' => 'google',
        'utm_medium' => 'organic',
    ]);

    expect($lead->fresh()->traffic_source)->toBe('seo_google');

    $click = PhoneClick::query()->create([
        'phone' => '+14155550999',
        'page_url' => 'https://www.deluxewindows.com/',
        'msclkid' => 'abc',
    ]);

    expect($click->fresh()->traffic_source)->toBe('microsoft_ads');
});

test('bucket mapping matches the product labels', function () {
    $visibility = app(TrafficSourceVisibility::class);

    expect(TrafficSourceVisibility::bucketForKey('google_ads'))->toBe('adwords')
        ->and(TrafficSourceVisibility::bucketForKey('microsoft_ads'))->toBe('bing')
        ->and(TrafficSourceVisibility::bucketForKey('seo_google'))->toBe('seo')
        ->and(TrafficSourceVisibility::bucketForKey('direct'))->toBe('direct')
        ->and(TrafficSourceVisibility::bucketForKey('referral'))->toBe('other');

    $user = userWithTrafficPermissions(trafficPermissions(
        TrafficSourceVisibility::SECTION_LEADS,
        ['adwords', 'bing', 'seo', 'direct', 'other']
    ));

    expect($visibility->allowedSourceKeys($user, TrafficSourceVisibility::SECTION_LEADS))
        ->toContain('google_ads', 'microsoft_ads', 'seo_google', 'direct', 'referral');
});
