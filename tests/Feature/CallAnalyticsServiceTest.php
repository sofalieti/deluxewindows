<?php

declare(strict_types=1);

use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Services\CallAnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-09 18:00:00', 'America/Los_Angeles'));
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function makeAnalyticsCall(array $overrides = []): RingCentralCall
{
    return RingCentralCall::query()->create(array_merge([
        'ringcentral_call_id' => 'call-'.uniqid(),
        'direction' => 'Inbound',
        'result' => 'Accepted',
        'started_at' => CarbonImmutable::parse('2026-08-08T18:00:00Z'),
        'duration' => 90,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505551212',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505551212',
        'synced_at' => now(),
    ], $overrides));
}

function analyticsPeriod(): array
{
    return app(CallAnalyticsService::class)->resolvePeriod(CallAnalyticsService::PRESET_LAST_7);
}

test('resolvePeriod last 7 days uses pacific day bounds', function () {
    $period = app(CallAnalyticsService::class)->resolvePeriod(CallAnalyticsService::PRESET_LAST_7);

    expect($period['start']->timezone->getName())->toBe('America/Los_Angeles')
        ->and($period['start']->toDateString())->toBe('2026-08-03')
        ->and($period['end']->toDateString())->toBe('2026-08-10')
        ->and($period['previousEnd']->equalTo($period['start']))->toBeTrue()
        ->and($period['previousStart']->toDateString())->toBe('2026-07-27');
});

test('resolvePeriod custom range respects start and end dates', function () {
    $period = app(CallAnalyticsService::class)->resolvePeriod(CallAnalyticsService::PRESET_CUSTOM, [
        'start' => '2026-08-01',
        'end' => '2026-08-05',
    ]);

    expect($period['preset'])->toBe(CallAnalyticsService::PRESET_CUSTOM)
        ->and($period['start']->toDateString())->toBe('2026-08-01')
        ->and($period['end']->subSecond()->toDateString())->toBe('2026-08-05');
});

test('kpis count total connected rate and missed calls', function () {
    makeAnalyticsCall([
        'ringcentral_call_id' => 'kpi-1',
        'external_phone' => '+16505550001',
        'result' => 'Accepted',
        'duration' => 120,
        'started_at' => CarbonImmutable::parse('2026-08-08T17:00:00Z'),
    ]);
    makeAnalyticsCall([
        'ringcentral_call_id' => 'kpi-2',
        'external_phone' => '+16505550002',
        'result' => 'Missed',
        'duration' => 0,
        'started_at' => CarbonImmutable::parse('2026-08-08T18:00:00Z'),
    ]);
    makeAnalyticsCall([
        'ringcentral_call_id' => 'kpi-3',
        'external_phone' => '+16505550003',
        'result' => 'Accepted',
        'duration' => 40,
        'started_at' => CarbonImmutable::parse('2026-08-08T19:00:00Z'),
    ]);
    // Outside period
    makeAnalyticsCall([
        'ringcentral_call_id' => 'kpi-old',
        'external_phone' => '+16505550099',
        'started_at' => CarbonImmutable::parse('2026-07-20T18:00:00Z'),
    ]);

    $period = analyticsPeriod();
    $data = app(CallAnalyticsService::class)->compute(
        $period['start'],
        $period['end'],
        $period['previousStart'],
        $period['previousEnd'],
        mainLinesOnly: false,
    );

    expect($data['kpis']['total_calls']['value'])->toBe('3')
        ->and($data['summary']['connected'])->toBe(2)
        ->and($data['summary']['missed'])->toBe(1)
        ->and($data['kpis']['connected_rate']['value'])->toBe('66.7%')
        ->and($data['kpis']['missed']['value'])->toBe('1');
});

test('new vs returning callers uses first-ever call in journal', function () {
    // Returning caller: first call before period, another in period
    makeAnalyticsCall([
        'ringcentral_call_id' => 'ret-old',
        'external_phone' => '+16505551111',
        'started_at' => CarbonImmutable::parse('2026-07-15T18:00:00Z'),
    ]);
    makeAnalyticsCall([
        'ringcentral_call_id' => 'ret-new',
        'external_phone' => '+16505551111',
        'started_at' => CarbonImmutable::parse('2026-08-07T18:00:00Z'),
    ]);

    // Brand-new caller in period
    makeAnalyticsCall([
        'ringcentral_call_id' => 'brand-new',
        'external_phone' => '+16505552222',
        'started_at' => CarbonImmutable::parse('2026-08-08T18:00:00Z'),
    ]);

    $period = analyticsPeriod();
    $data = app(CallAnalyticsService::class)->compute(
        $period['start'],
        $period['end'],
        $period['previousStart'],
        $period['previousEnd'],
        mainLinesOnly: false,
    );

    expect($data['summary']['new_callers'])->toBe(1)
        ->and($data['summary']['returning_callers'])->toBe(1)
        ->and($data['kpis']['new_callers']['value'])->toBe('1');
});

test('by source groups confirmed phone clicks matched to calls', function () {
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'bay-windows',
        'gclid' => 'gclid-1',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-08T18:00:00Z'),
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'msclkid' => 'ms-1',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-08T19:00:00Z'),
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'gclid' => 'gclid-spam',
        'is_spam' => true,
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-08T20:00:00Z'),
    ]);

    $period = analyticsPeriod();
    $data = app(CallAnalyticsService::class)->compute(
        $period['start'],
        $period['end'],
        $period['previousStart'],
        $period['previousEnd'],
        mainLinesOnly: false,
    );

    expect($data['source_chart'][0]['labels'] ?? [])->toContain('Google Ads')
        ->and($data['source_chart'][0]['labels'] ?? [])->toContain('Microsoft Ads')
        ->and(array_sum($data['source_chart'][0]['values'] ?? []))->toBe(2)
        ->and($data['top_campaigns'][0]['campaign'] ?? null)->toBe('bay-windows')
        ->and($data['kpis']['paid_ads_calls']['value'])->toBe('2');
});

test('admin call analytics screen is reachable with permission', function () {
    $user = User::factory()->create([
        'permissions' => [
            'platform.index' => true,
            'platform.analytics' => true,
        ],
    ]);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.analytics.calls'))
        ->assertOk()
        ->assertSee('Call Analytics');
});
