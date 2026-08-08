<?php

declare(strict_types=1);

use App\Models\SiteVisit;
use App\Models\User;
use App\Models\VisitsSetting;
use App\Services\VisitsSettingsService;
use Orchid\Platform\Http\Middleware\Access;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

beforeEach(function () {
    SiteVisit::query()->delete();
    app(VisitsSettingsService::class)->forgetCache();
});

function enableVisitTracking(bool $enabled = true): void
{
    VisitsSetting::query()->updateOrCreate(
        ['scope' => 'default'],
        ['enabled' => $enabled]
    );
    app(VisitsSettingsService::class)->forgetCache();
}

test('visit store returns 204 and writes nothing when tracking is disabled', function () {
    enableVisitTracking(false);

    postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'google',
        'utm_city' => '9032015',
        'gclid' => 'test-gclid',
    ])->assertNoContent();

    assertDatabaseCount('site_visits', 0);
});

test('visit store records attribution when tracking is enabled', function () {
    enableVisitTracking(true);

    postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/?utm_city=9032015',
        'landing_page' => '/',
        'first_landing_page' => '/',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'bay-area',
        'utm_city' => '9032015',
        'first_utm_city' => '9032015',
        'gclid' => 'abc123',
        'first_gclid' => 'abc123',
    ])->assertOk()->assertJson(['ok' => true]);

    $visit = SiteVisit::query()->latest('id')->firstOrFail();

    expect($visit->utm_city)->toBe('9032015')
        ->and($visit->first_utm_city)->toBe('9032015')
        ->and($visit->utm_source)->toBe('google')
        ->and($visit->gclid)->toBe('abc123')
        ->and($visit->landing_page)->toBe('/')
        ->and($visit->traffic_source)->toBe('google_ads')
        ->and($visit->meta['via'] ?? null)->toBe('site-visit');
});

test('admin can clear all visits', function () {
    enableVisitTracking(true);

    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'google',
        'utm_city' => '1013802',
    ]);
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/windows',
        'utm_source' => '(direct)',
    ]);

    assertDatabaseCount('site_visits', 2);

    $user = User::factory()->create([
        'permissions' => [
            'platform.index' => true,
            'platform.visits' => true,
        ],
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.visits', ['method' => 'clearAll']))
        ->assertRedirect();

    assertDatabaseCount('site_visits', 0);
});

test('admin can toggle visit tracking', function () {
    enableVisitTracking(false);

    $user = User::factory()->create([
        'permissions' => [
            'platform.index' => true,
            'platform.visits' => true,
        ],
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.visits', ['method' => 'saveSettings']), [
            'setting' => ['enabled' => '1'],
        ])
        ->assertRedirect();

    expect(app(VisitsSettingsService::class)->enabled())->toBeTrue();
});
