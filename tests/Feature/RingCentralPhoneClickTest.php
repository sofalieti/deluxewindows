<?php

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use App\Services\RingCentralCallLogService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('a phone click dispatches a RingCentral lookup three minutes later', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:00:00 UTC');

    $this->postJson('/phone-click', [
        'phone' => '+1 (650) 461-4446',
        'page_url' => 'https://www.deluxewindows.com/doors',
        'source_label' => 'header-phone',
    ])->assertOk()->assertJson(['ok' => true]);

    $click = PhoneClick::query()->sole();

    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_PENDING);

    Queue::assertPushed(
        MatchPhoneClickToRingCentral::class,
        fn (MatchPhoneClickToRingCentral $job): bool => $job->phoneClickId === $click->id
            && CarbonImmutable::parse($job->delay)->equalTo(now()->addMinutes(3))
    );
});

test('the RingCentral service authenticates with JWT and caches its token', function () {
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');
    $click = PhoneClick::query()->create([
        'phone' => '+1 (650) 461-4446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'call-100',
                'sessionId' => 'session-100',
                'telephonySessionId' => 'telephony-100',
                'startTime' => '2026-07-30T16:00:25.000Z',
                'duration' => 82,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Call connected',
                'from' => ['phoneNumber' => '+14155550123'],
                'to' => ['phoneNumber' => '+16504614446'],
            ]],
        ]),
    ]);

    $service = app(RingCentralCallLogService::class);
    $first = $service->findMatchingCall($click);
    $second = $service->findMatchingCall($click);

    expect($first)->not->toBeNull()
        ->and($first['id'])->toBe('call-100')
        ->and($first['result'])->toBe('Call connected')
        ->and($first['duration'])->toBe(82)
        ->and($first['from_phone'])->toBe('+14155550123')
        ->and($second['id'])->toBe('call-100');

    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request): bool => $request->url()
        === 'https://platform.ringcentral.test/restapi/oauth/token'
        && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
        && $request['assertion'] === 'test-personal-jwt');
});

test('the queued lookup stores any RingCentral call attempt and its result', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    fakeRingCentralCallLog([[
        'id' => 'call-missed-1',
        'sessionId' => 'session-missed-1',
        'startTime' => '2026-07-30T16:00:40.000Z',
        'duration' => 12,
        'type' => 'Voice',
        'direction' => 'Inbound',
        'result' => 'Missed',
        'from' => ['phoneNumber' => '+14155550999'],
        'to' => ['phoneNumber' => '+16504614446'],
    ]]);

    (new MatchPhoneClickToRingCentral($click->id))
        ->handle(app(RingCentralCallLogService::class));

    $click->refresh();

    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($click->ringcentral_result)->toBe('Missed')
        ->and($click->ringcentral_duration)->toBe(12)
        ->and($click->ringcentral_from_phone)->toBe('+14155550999')
        ->and($click->ringcentral_attempts)->toBe(1);

    Queue::assertNothingPushed();
});

test('a missing call is retried and becomes no call at the end of the window', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    fakeRingCentralCallLog([]);
    $job = new MatchPhoneClickToRingCentral($click->id);
    $job->handle(app(RingCentralCallLogService::class));

    $click->refresh();
    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_PENDING)
        ->and($click->ringcentral_attempts)->toBe(1);
    Queue::assertPushed(MatchPhoneClickToRingCentral::class);

    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:10:00 UTC');
    $job->handle(app(RingCentralCallLogService::class));

    $click->refresh();
    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_NO_CALL)
        ->and($click->ringcentral_attempts)->toBe(2);
    Queue::assertNothingPushed();
});

test('a legacy future checked timestamp does not stop RingCentral retries', function () {
    Queue::fake();
    config()->set('app.timezone', 'America/Los_Angeles');
    CarbonImmutable::setTestNow('2026-07-30 01:18:59 America/Los_Angeles');
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
        'ringcentral_attempts' => 1,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 01:13:57 America/Los_Angeles'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 01:13:57 America/Los_Angeles'),
        // Reproduces timestamps written as UTC and then interpreted as app time.
        'ringcentral_checked_at' => CarbonImmutable::parse('2026-07-30 08:16:58 America/Los_Angeles'),
    ])->saveQuietly();

    fakeRingCentralCallLog([]);

    (new MatchPhoneClickToRingCentral($click->id))
        ->handle(app(RingCentralCallLogService::class));

    $click->refresh();
    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_PENDING)
        ->and($click->ringcentral_attempts)->toBe(2);
    Queue::assertPushed(MatchPhoneClickToRingCentral::class);
});

test('a RingCentral API failure is retried and ends with an error status', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([], 503),
    ]);

    $job = new MatchPhoneClickToRingCentral($click->id);
    $job->handle(app(RingCentralCallLogService::class));

    $click->refresh();
    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_PENDING)
        ->and($click->ringcentral_error)->toContain('HTTP 503');
    Queue::assertPushed(MatchPhoneClickToRingCentral::class);

    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:10:00 UTC');
    $job->handle(app(RingCentralCallLogService::class));

    $click->refresh();
    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_ERROR)
        ->and($click->ringcentral_error)->toContain('HTTP 503');
    Queue::assertNothingPushed();
});

test('one RingCentral call cannot be assigned to two phone clicks', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:10:02 UTC');
    $createdAt = CarbonImmutable::parse('2026-07-30 16:00:00 UTC');
    $firstClick = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $secondClick = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $firstClick->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();
    $secondClick->forceFill([
        'created_at' => $createdAt->addSecond(),
        'updated_at' => $createdAt->addSecond(),
    ])->saveQuietly();

    fakeRingCentralCallLog([[
        'id' => 'one-call-only',
        'sessionId' => 'one-session-only',
        'startTime' => '2026-07-30T16:00:30.000Z',
        'duration' => 45,
        'type' => 'Voice',
        'direction' => 'Inbound',
        'result' => 'Call connected',
        'from' => ['phoneNumber' => '+14155550111'],
        'to' => ['phoneNumber' => '+16504614446'],
    ]]);

    (new MatchPhoneClickToRingCentral($firstClick->id))
        ->handle(app(RingCentralCallLogService::class));
    (new MatchPhoneClickToRingCentral($secondClick->id))
        ->handle(app(RingCentralCallLogService::class));

    expect($firstClick->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($secondClick->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_NO_CALL)
        ->and(PhoneClick::query()->where('ringcentral_call_id', 'one-call-only')->count())->toBe(1);
});

test('phone click matching also checks additional RingCentral numbers from promotions', function () {
    Queue::fake();
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');

    \App\Models\PromotionControl::query()->updateOrCreate(
        ['scope' => 'default'],
        [
            'phone_display' => '(650) 461-4446',
            'phone_tel' => '+16504614446',
            'ringcentral_extra_phones' => ['+14155550199'],
        ],
    );
    app(\App\Services\PromotionControlService::class)->forgetCache();

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), '/oauth/token')) {
            return Http::response([
                'access_token' => 'test-access-token',
                'expires_in' => 3600,
            ]);
        }

        $phone = $request['phoneNumber'] ?? '';
        if ($phone === '+14155550199') {
            return Http::response([
                'records' => [[
                    'id' => 'extra-did-call',
                    'sessionId' => 'extra-session',
                    'startTime' => '2026-07-30T16:00:20.000Z',
                    'duration' => 33,
                    'type' => 'Voice',
                    'direction' => 'Inbound',
                    'result' => 'Accepted',
                    'from' => ['phoneNumber' => '+14155550888'],
                    'to' => ['phoneNumber' => '+14155550199'],
                ]],
            ]);
        }

        return Http::response(['records' => []]);
    });

    (new MatchPhoneClickToRingCentral($click->id))
        ->handle(app(RingCentralCallLogService::class));

    $click->refresh();

    expect($click->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($click->ringcentral_call_id)->toBe('extra-did-call')
        ->and($click->ringcentral_to_phone)->toBe('+14155550199')
        ->and($click->ringcentral_from_phone)->toBe('+14155550888');
});

test('phone clicks screen can trigger an immediate RingCentral re-check', function () {
    CarbonImmutable::setTestNow('2026-07-30 16:03:00 UTC');
    $user = \App\Models\User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-07-30 16:00:00 UTC'),
    ])->saveQuietly();

    fakeRingCentralCallLog([[
        'id' => 'admin-check-call',
        'sessionId' => 'admin-check-session',
        'startTime' => '2026-07-30T16:00:15.000Z',
        'duration' => 10,
        'type' => 'Voice',
        'direction' => 'Inbound',
        'result' => 'Accepted',
        'from' => ['phoneNumber' => '+14155550777'],
        'to' => ['phoneNumber' => '+16504614446'],
    ]]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->post(route('platform.phone-clicks', ['method' => 'checkRingCentralNow']))
        ->assertRedirect();

    expect($click->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($click->ringcentral_call_id)->toBe('admin-check-call');
});

function fakeRingCentralCallLog(array $records): void
{
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => $records,
        ]),
    ]);
}
