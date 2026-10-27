<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\SiteEvent;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\VisitsSetting;
use App\Services\BotDetector;
use App\Services\VisitorJourney;
use App\Services\VisitsSettingsService;
use App\Support\ClarityDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

const BEHAVIOR_VISITOR = '11111111-1111-4111-8111-111111111111';
const BEHAVIOR_SESSION = '22222222-2222-4222-8222-222222222222';

function enableBehavior(bool $enabled = true): void
{
    VisitsSetting::query()->updateOrCreate(
        ['scope' => 'behavior'],
        ['enabled' => $enabled]
    );
    app(VisitsSettingsService::class)->forgetCache();
}

function behaviorPayload(array $events): array
{
    return [
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
        'events' => $events,
    ];
}

beforeEach(function () {
    app(VisitsSettingsService::class)->forgetCache();
});

test('behavior events are ignored when tracking is off', function () {
    enableBehavior(false);

    postJson('/t/events', behaviorPayload([
        ['type' => 'page_view', 'path' => '/'],
    ]))->assertNoContent();

    assertDatabaseCount('site_events', 0);
});

test('known crawlers are dropped and ordinary phones are kept', function () {
    $detector = app(BotDetector::class);

    expect($detector->isBot('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'))->toBeTrue()
        ->and($detector->isBot('Mozilla/5.0 (Linux; Android 11; CUBOT KINGKONG) AppleWebKit/537.36 Chrome/90.0.4430.210 Mobile Safari/537.36'))->toBeFalse();

    enableBehavior(true);

    postJson('/t/events', behaviorPayload([
        ['type' => 'page_view', 'path' => '/'],
    ]), [
        'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
    ])->assertNoContent();

    assertDatabaseCount('site_events', 0);
});

test('a batch updates the session summary and drops form values', function () {
    enableBehavior(true);

    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'session_id' => BEHAVIOR_SESSION,
        'visitor_id' => BEHAVIOR_VISITOR,
    ]);

    postJson('/t/events', behaviorPayload([
        ['type' => 'page_view', 'path' => '/', 'meta' => ['title' => 'Home', 'referrer' => '/doors']],
        ['type' => 'page_view', 'path' => '/windows'],
        ['type' => 'scroll_depth', 'path' => '/windows', 'value' => 25],
        ['type' => 'scroll_depth', 'path' => '/windows', 'value' => 75],
        ['type' => 'engaged_time', 'path' => '/windows', 'value' => 12],
        ['type' => 'rage_click', 'path' => '/windows', 'target' => 'button', 'value' => 3],
        [
            'type' => 'click',
            'path' => '/windows',
            'target' => 'Call 650-555-1212',
            'value' => 40,
            'meta' => [
                'y' => 80,
                'field' => 'email@example.com',
                'value' => 'secret',
                'title' => 'Windows',
            ],
        ],
    ]), [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
    ])->assertNoContent();

    expect(SiteEvent::query()->count())->toBe(7);

    $click = SiteEvent::query()->where('type', 'click')->firstOrFail();
    expect($click->target)->toBe('Call #')
        ->and($click->meta)->toBe(['title' => 'Windows', 'y' => 80])
        ->and($click->visitor_id)->toBe(BEHAVIOR_VISITOR);

    $visit = SiteVisit::query()->where('session_id', BEHAVIOR_SESSION)->firstOrFail();
    expect($visit->pageviews)->toBe(2)
        ->and($visit->max_scroll)->toBe(75)
        ->and($visit->engaged_seconds)->toBe(12)
        ->and($visit->rage_clicks)->toBe(1)
        ->and($visit->events_count)->toBe(7)
        ->and($visit->last_seen_at)->not->toBeNull();
});

test('batches larger than 50 events are rejected', function () {
    enableBehavior(true);

    $events = array_fill(0, 51, ['type' => 'page_view', 'path' => '/']);

    postJson('/t/events', behaviorPayload($events))->assertStatus(422);

    assertDatabaseCount('site_events', 0);
});

test('events older than 90 days are pruned', function () {
    SiteEvent::query()->create([
        'session_id' => BEHAVIOR_SESSION,
        'visitor_id' => BEHAVIOR_VISITOR,
        'type' => 'page_view',
        'created_at' => now()->subDays(91),
    ]);
    SiteEvent::query()->create([
        'session_id' => BEHAVIOR_SESSION,
        'visitor_id' => BEHAVIOR_VISITOR,
        'type' => 'click',
        'created_at' => now()->subDays(10),
    ]);

    $this->artisan('model:prune', [
        '--model' => [SiteEvent::class],
    ])->assertSuccessful();

    expect(SiteEvent::query()->count())->toBe(1)
        ->and(SiteEvent::query()->value('type'))->toBe('click');
});

test('a lead keeps the visitor id and the path stops at the conversion', function () {
    $early = SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'landing_page' => '/',
        'utm_source' => 'google',
        'gclid' => 'gclid-path',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
    ]);
    $early->created_at = now()->subHour();
    $early->save();

    postJson('/contact-form', [
        'Name' => 'Pat Smith',
        'Phone' => '6505551212',
        'Email' => 'pat@example.com',
        'window_count' => '3-5',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
        'page_url' => 'https://www.deluxewindows.com/windows',
    ])->assertOk();

    $lead = Lead::query()->sole();
    expect($lead->visitor_id)->toBe(BEHAVIOR_VISITOR)
        ->and($lead->session_id)->toBe(BEHAVIOR_SESSION);

    $later = SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/doors',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => '33333333-3333-4333-8333-333333333333',
    ]);
    $later->created_at = now()->addHour();
    $later->save();

    $journey = app(VisitorJourney::class)->visits($lead->visitor_id, $lead->created_at);

    expect($journey)->toHaveCount(1)
        ->and($journey->first()->id)->toBe($early->id);

    $html = view('admin.behavior.journey', [
        'journey' => $journey,
        'clarityUrl' => null,
    ])->render();

    expect($html)->toContain('Customer path')
        ->and($html)->toContain('Google');
});

test('a phone click stores the visitor id and counts as a conversion', function () {
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
    ]);
    SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/about',
        'visitor_id' => '44444444-4444-4444-8444-444444444444',
    ]);

    postJson('/phone-click', [
        'phone' => '6505551212',
        'page_url' => 'https://www.deluxewindows.com/',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
    ])->assertOk();

    expect(PhoneClick::query()->sole()->visitor_id)->toBe(BEHAVIOR_VISITOR)
        ->and(SiteVisit::query()->converted()->count())->toBe(1)
        ->and(SiteVisit::query()->notConverted()->count())->toBe(1);
});

test('a visit flags bots and drops an invalid visitor id', function () {
    VisitsSetting::query()->updateOrCreate(['scope' => 'default'], ['enabled' => true]);
    app(VisitsSettingsService::class)->forgetCache();

    postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/',
        'visitor_id' => 'not-a-uuid',
        'session_id' => BEHAVIOR_SESSION,
    ], [
        'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1)',
    ])->assertOk();

    $visit = SiteVisit::query()->sole();
    expect($visit->is_bot)->toBeTrue()
        ->and($visit->visitor_id)->toBeNull()
        ->and($visit->session_id)->toBe(BEHAVIOR_SESSION);
});

test('clarity links and the public scripts follow the project id and the behavior switch', function () {
    config(['services.clarity.project_id' => 'abc123']);

    expect(ClarityDashboard::recordingsUrl(BEHAVIOR_VISITOR))
        ->toContain('abc123')
        ->toContain('CustomUserId');

    enableBehavior(true);

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertSee('clarity.ms/tag/', false)
        ->assertSee('"abc123"', false)
        ->assertSee('\/t\/events', false)
        ->assertSee('payload.visitor_id', false);

    config(['services.clarity.project_id' => '']);
    enableBehavior(false);

    expect(ClarityDashboard::recordingsUrl(BEHAVIOR_VISITOR))->toBeNull();

    $this->get('/privacy-policy')
        ->assertOk()
        ->assertDontSee('clarity.ms/tag/', false)
        ->assertDontSee('dw_evt_n', false)
        ->assertSee('DeluxeClarity', false);
});

test('clearing visits also clears behavior events', function () {
    $visit = SiteVisit::query()->create([
        'page_url' => 'https://www.deluxewindows.com/',
        'visitor_id' => BEHAVIOR_VISITOR,
        'session_id' => BEHAVIOR_SESSION,
    ]);
    SiteEvent::query()->create([
        'session_id' => BEHAVIOR_SESSION,
        'visitor_id' => BEHAVIOR_VISITOR,
        'type' => 'page_view',
        'path' => '/',
    ]);

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
        ->assertSee('Pages');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.visits.view', $visit))
        ->assertOk()
        ->assertSee('Page view');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.visits', ['method' => 'clearAll']))
        ->assertRedirect();

    assertDatabaseCount('site_visits', 0);
    assertDatabaseCount('site_events', 0);
});
