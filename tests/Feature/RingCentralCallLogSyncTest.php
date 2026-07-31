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
        ->and($second['from'])->toStartWith('2026-07-31T18:25:00')
        ->and(RingCentralCall::query()->count())->toBe(1)
        ->and(RingCentralCallSyncState::query()->sole()->last_synced_at)->not->toBeNull();
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
