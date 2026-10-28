<?php

declare(strict_types=1);

use App\Models\SiteVisit;
use App\Models\User;
use App\Models\VisitsSetting;
use App\Orchid\Screens\Visits\VisitListScreen;
use App\Services\VisitsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

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
        'visitor_id' => '11111111-1111-4111-8111-111111111111',
        'session_id' => '22222222-2222-4222-8222-222222222222',
    ])->assertOk()->assertJson(['ok' => true]);

    $visit = SiteVisit::query()->latest('id')->firstOrFail();

    expect($visit->utm_city)->toBe('9032015')
        ->and($visit->first_utm_city)->toBe('9032015')
        ->and($visit->utm_source)->toBe('google')
        ->and($visit->gclid)->toBe('abc123')
        ->and($visit->landing_page)->toBe('/')
        ->and($visit->traffic_source)->toBe('google_ads')
        ->and($visit->visitor_id)->toBe('11111111-1111-4111-8111-111111111111')
        ->and($visit->session_id)->toBe('22222222-2222-4222-8222-222222222222')
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

test('visit list splits microsoft, google, seo, and direct into their own tabs', function () {
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/from-microsoft',
        'msclkid' => 'ms-click',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
    ]);
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/from-google',
        'gclid' => 'g-click',
    ]);
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/from-seo',
        'referrer' => 'https://www.google.com/search?q=windows',
    ]);
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/from-direct',
    ]);

    $screen = app(VisitListScreen::class)->query();

    expect($screen['microsoft']->pluck('page_url')->all())->toBe(['https://www.deluxewindows.com/from-microsoft'])
        ->and($screen['google']->pluck('page_url')->all())->toBe(['https://www.deluxewindows.com/from-google'])
        ->and($screen['seo']->pluck('page_url')->all())->toBe(['https://www.deluxewindows.com/from-seo'])
        ->and($screen['direct']->pluck('page_url')->all())->toBe(['https://www.deluxewindows.com/from-direct'])
        ->and($screen['visits']->total())->toBe(4);

    $user = User::factory()->create([
        'permissions' => [
            'platform.index' => true,
            'platform.visits' => true,
        ],
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.visits'))
        ->assertOk()
        ->assertSee('Microsoft')
        ->assertSee('Google')
        ->assertSee('SEO')
        ->assertSee('Direct');
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
            'setting' => [
                'enabled' => '1',
                'behavior_enabled' => '1',
            ],
        ])
        ->assertRedirect();

    expect(app(VisitsSettingsService::class)->enabled())->toBeTrue()
        ->and(app(VisitsSettingsService::class)->behaviorEnabled())->toBeTrue();
});
