<?php

use App\Models\PromotionControl;
use App\Models\RingCentralCall;
use App\Models\RingCentralCallSyncState;
use App\Models\RingCentralExcludedNumber;
use App\Models\User;
use App\Services\RingCentralCallLogService;
use App\Services\RingCentralCallSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.ringcentral', [
        'base_url' => 'https://platform.ringcentral.test',
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'jwt' => 'test-personal-jwt',
        'account_id' => '~',
        'match_window_minutes' => 10,
        'clock_tolerance_seconds' => 30,
        'retry_delay_seconds' => 120,
    ]);

    Cache::flush();
    PromotionControl::query()->updateOrCreate(
        ['scope' => 'default'],
        [
            'global_promotion_name' => 'Test',
            'global_discount_percent' => 40,
            'phone_display' => '(650) 461-4446',
            'phone_tel' => '+16504614446',
        ],
    );
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('company call log pagination returns inbound and outbound calls for the admin number', function () {
    fakePaginatedRingCentralCalls();

    $calls = app(RingCentralCallLogService::class)->fetchCalls(
        '+16504614446',
        CarbonImmutable::parse('2026-07-31 07:00:00 UTC'),
        CarbonImmutable::parse('2026-07-31 18:00:00 UTC'),
    );

    expect($calls)->toHaveCount(2)
        ->and(collect($calls)->pluck('direction')->sort()->values()->all())->toBe(['Inbound', 'Outbound'])
        ->and($calls[0]['external_phone'])->toBe('+14155550111')
        ->and($calls[1]['external_phone'])->toBe('+14155550222');

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), '/call-log')) {
            return false;
        }

        return isset($request['phoneNumber'])
            && $request['phoneNumber'] === '+16504614446'
            && $request['type'] === 'Voice'
            && ! isset($request['direction']);
    });
});

test('hourly sync starts at California midnight then overlaps and upserts', function () {
    CarbonImmutable::setTestNow('2026-07-31 18:30:00 UTC');
    fakeSingleRingCentralCall('First result');

    $first = app(RingCentralCallSyncService::class)->sync();

    expect($first['created'])->toBe(1)
        ->and($first['from'])->toStartWith('2026-07-31T07:00:00')
        ->and(RingCentralCall::query()->count())->toBe(1);

    CarbonImmutable::setTestNow('2026-07-31 19:30:00 UTC');
    Http::fake();
    Cache::flush();
    fakeSingleRingCentralCall('Updated result');

    $second = app(RingCentralCallSyncService::class)->sync();

    expect($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(1)
        ->and($second['from'])->toStartWith('2026-07-31T18:30:00')
        ->and(RingCentralCall::query()->count())->toBe(1)
        ->and(RingCentralCallSyncState::query()->sole()->last_synced_at)->not->toBeNull();

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), '/call-log')) {
            return false;
        }

        // Second sync must continue from previous checkpoint with a small overlap.
        return ($request['dateFrom'] ?? '') === '2026-07-31T18:25:00.000Z'
            && str_starts_with((string) ($request['dateTo'] ?? ''), '2026-07-31T19:30:00');
    });
});

test('sync remembers the previous checkpoint across app timezone casts', function () {
    config()->set('app.timezone', 'America/Los_Angeles');
    CarbonImmutable::setTestNow('2026-07-31 18:00:00 UTC');
    fakeSingleRingCentralCall('First');

    app(RingCentralCallSyncService::class)->sync();

    $state = RingCentralCallSyncState::query()->sole();
    expect($state->last_synced_at)->not->toBeNull();

    CarbonImmutable::setTestNow('2026-07-31 20:00:00 UTC');
    Cache::flush();
    fakeSingleRingCentralCall('Second');

    $second = app(RingCentralCallSyncService::class)->sync();

    expect($second['from'])->toStartWith('2026-07-31T18:00:00')
        ->and($second['to'])->toStartWith('2026-07-31T20:00:00');

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), '/call-log')) {
            return false;
        }

        return ($request['dateFrom'] ?? '') === '2026-07-31T17:55:00.000Z';
    });
});

test('a failed API request does not advance the sync checkpoint', function () {
    $state = RingCentralCallSyncState::query()->create([
        'business_phone' => '+16504614446',
        'started_at' => CarbonImmutable::parse('2026-07-31 07:00:00 UTC'),
        'last_synced_at' => CarbonImmutable::parse('2026-07-31 18:00:00 UTC'),
    ]);
    $checkpoint = $state->getRawOriginal('last_synced_at');
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([], 503),
    ]);

    expect(fn () => app(RingCentralCallSyncService::class)->sync(
        CarbonImmutable::parse('2026-07-31 19:00:00 UTC')
    ))->toThrow(RuntimeException::class);

    expect($state->refresh()->getRawOriginal('last_synced_at'))->toBe($checkpoint);
});

test('Orchid hides an excluded number and restore returns its calls', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();
    $call = createStoredRingCentralCall('+14155550333');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.ringcentral-calls', ['method' => 'excludeNumber']), [
            'call' => $call->id,
        ])
        ->assertRedirect();

    $exclusion = RingCentralExcludedNumber::query()->sole();
    expect(RingCentralCall::query()->visible()->count())->toBe(0)
        ->and($exclusion->phone)->toBe('+14155550333');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.ringcentral-calls'))
        ->assertOk();

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.ringcentral-calls', ['method' => 'restoreNumber']), [
            'exclusion' => $exclusion->id,
        ])
        ->assertRedirect();

    expect(RingCentralCall::query()->visible()->count())->toBe(1)
        ->and($exclusion->refresh()->restored_at)->not->toBeNull();
});

test('the current business number cannot be excluded', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();
    $call = createStoredRingCentralCall('+16504614446');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.ringcentral-calls', ['method' => 'excludeNumber']), [
            'call' => $call->id,
        ])
        ->assertRedirect();

    expect(RingCentralExcludedNumber::query()->count())->toBe(0);
});

test('Orchid Sync now button pulls calls from RingCentral', function () {
    CarbonImmutable::setTestNow('2026-07-31 18:30:00 UTC');
    fakeSingleRingCentralCall('Accepted');

    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->post(route('platform.ringcentral-calls', ['method' => 'syncNow']))
        ->assertRedirect();

    expect(RingCentralCall::query()->count())->toBe(1)
        ->and(RingCentralCallSyncState::query()->where('business_phone', '+16504614446')->value('last_synced_at'))
        ->not->toBeNull();
});

test('phone formatting variants are treated as the same number', function () {
    $service = app(RingCentralCallLogService::class);

    expect($service->normalizePhone('(650) 461-4446'))->toBe('+16504614446')
        ->and($service->normalizePhone('650-461-4446'))->toBe('+16504614446')
        ->and($service->normalizePhone('+1 650 461 4446'))->toBe('+16504614446')
        ->and($service->phonesMatch('(650) 461-4446', '+16504614446'))->toBeTrue()
        ->and($service->phonesMatch('6504614446', '+16504614446'))->toBeTrue();
});

test('sync keeps calls when RingCentral returns a formatted business number', function () {
    CarbonImmutable::setTestNow('2026-07-31 18:30:00 UTC');
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'formatted-call-1',
                'sessionId' => 'formatted-session-1',
                'startTime' => '2026-07-31T18:00:00.000Z',
                'duration' => 45,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '(415) 555-0444'],
                'to' => ['phoneNumber' => '(650) 461-4446'],
            ]],
        ]),
    ]);

    $result = app(RingCentralCallSyncService::class)->sync();

    expect($result['fetched'])->toBe(1)
        ->and(RingCentralCall::query()->sole()->external_phone)->toBe('+14155550444')
        ->and(RingCentralCall::query()->sole()->business_phone)->toBe('+16504614446');
});

test('extra RingCentral phones from promotions are synced too', function () {
    CarbonImmutable::setTestNow('2026-07-31 18:30:00 UTC');

    PromotionControl::query()->updateOrCreate(
        ['scope' => 'default'],
        [
            'phone_display' => '(650) 461-4446',
            'phone_tel' => '+16504614446',
            'ringcentral_extra_phones' => ['(415) 555-0199'],
        ],
    );
    app(\App\Services\PromotionControlService::class)->forgetCache();

    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        if (str_contains($request->url(), '/oauth/token')) {
            return Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]);
        }

        $phone = $request['phoneNumber'] ?? '';
        $id = $phone === '+14155550199' ? 'extra-call' : 'primary-call';
        $to = $phone === '+14155550199' ? '+14155550199' : '+16504614446';

        return Http::response([
            'records' => [[
                'id' => $id,
                'sessionId' => 'session-'.$id,
                'startTime' => '2026-07-31T18:00:00.000Z',
                'duration' => 20,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '+14155550000'],
                'to' => ['phoneNumber' => $to],
            ]],
        ]);
    });

    $result = app(RingCentralCallSyncService::class)->sync();

    expect($result['fetched'])->toBe(2)
        ->and($result['phones'])->toBe(['+16504614446', '+14155550199'])
        ->and(RingCentralCall::query()->pluck('business_phone')->sort()->values()->all())
        ->toBe(['+14155550199', '+16504614446']);
});

test('promotions normalize extra RingCentral phone lines', function () {
    expect(\App\Services\PromotionControlService::normalizeRingCentralExtraPhones(
        "(415) 555-0100\n\n+14155550100\n650-461-4446"
    ))->toBe(['+14155550100', '+16504614446']);
});

function fakePaginatedRingCentralCalls(): void
{
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::sequence()
            ->push([
                'records' => [[
                    'id' => 'inbound-1',
                    'sessionId' => 'session-inbound',
                    'startTime' => '2026-07-31T17:00:00.000Z',
                    'duration' => 30,
                    'type' => 'Voice',
                    'direction' => 'Inbound',
                    'result' => 'Accepted',
                    'from' => ['phoneNumber' => '+14155550111'],
                    'to' => ['phoneNumber' => '+16504614446'],
                ]],
                'navigation' => [
                    'nextPage' => [
                        'uri' => 'https://platform.ringcentral.test/restapi/v1.0/account/~/call-log?page=2',
                    ],
                ],
            ])
            ->push([
                'records' => [[
                    'id' => 'outbound-1',
                    'sessionId' => 'session-outbound',
                    'startTime' => '2026-07-31T17:30:00.000Z',
                    'duration' => 60,
                    'type' => 'Voice',
                    'direction' => 'Outbound',
                    'result' => 'Call connected',
                    'from' => ['phoneNumber' => '+16504614446'],
                    'to' => ['phoneNumber' => '+14155550222'],
                ]],
            ]),
    ]);
}

function fakeSingleRingCentralCall(string $result): void
{
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'sync-call-1',
                'sessionId' => 'sync-session-1',
                'startTime' => '2026-07-31T18:00:00.000Z',
                'duration' => 45,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => $result,
                'from' => ['phoneNumber' => '+14155550444'],
                'to' => ['phoneNumber' => '+16504614446'],
            ]],
        ]),
    ]);
}

function createStoredRingCentralCall(string $externalPhone): RingCentralCall
{
    return RingCentralCall::query()->create([
        'ringcentral_call_id' => 'stored-'.md5($externalPhone),
        'direction' => 'Inbound',
        'started_at' => now(),
        'duration' => 10,
        'business_phone' => '+16504614446',
        'from_phone' => $externalPhone,
        'to_phone' => '+16504614446',
        'external_phone' => $externalPhone,
        'synced_at' => now(),
    ]);
}
