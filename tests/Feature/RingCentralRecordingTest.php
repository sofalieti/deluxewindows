<?php

use App\Models\PhoneClick;
use App\Models\PromotionControl;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Services\RingCentralCallLogService;
use App\Services\RingCentralCallSyncService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.ringcentral', [
        'base_url' => 'https://platform.ringcentral.test',
        'media_url' => 'https://media.ringcentral.test',
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
        'jwt' => 'test-jwt',
        'account_id' => '~',
        'match_window_seconds' => 60,
        'lookup_window_minutes' => 10,
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

test('sync stores recording id from call log payload', function () {
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'recorded-call-1',
                'sessionId' => 'session-recorded',
                'startTime' => '2026-08-01T18:00:00.000Z',
                'duration' => 45,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '+14155550999'],
                'to' => ['phoneNumber' => '+16504614446'],
                'recording' => [
                    'id' => 'rec-401547458008',
                    'contentUri' => 'https://media.ringcentral.test/restapi/v1.0/account/~/recording/rec-401547458008/content',
                ],
            ]],
        ]),
    ]);

    CarbonImmutable::setTestNow('2026-08-01 19:00:00 UTC');
    app(RingCentralCallSyncService::class)->sync(forceDays: 1);

    $call = RingCentralCall::query()->where('ringcentral_call_id', 'recorded-call-1')->sole();

    expect($call->recording_id)->toBe('rec-401547458008')
        ->and($call->hasRecording())->toBeTrue()
        ->and($call->recordingUrl())->toContain('/recording');
});

test('admin can stream a call recording through the proxy', function () {
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://media.ringcentral.test/restapi/v1.0/account/*/recording/*/content*' => Http::response(
            'ID3fake-audio-bytes',
            200,
            ['Content-Type' => 'audio/mpeg']
        ),
    ]);

    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => [
            'platform.ringcentral-calls' => true,
            'platform.phone-clicks' => true,
        ],
    ])->save();

    $call = RingCentralCall::query()->create([
        'ringcentral_call_id' => 'proxy-call',
        'direction' => 'Inbound',
        'started_at' => now(),
        'duration' => 20,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155550111',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155550111',
        'recording_id' => 'rec-proxy-1',
        'synced_at' => now(),
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.ringcentral-calls.recording', $call))
        ->assertOk()
        ->assertHeader('Content-Type', 'audio/mpeg')
        ->assertSee('ID3fake-audio-bytes', false);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/recording/rec-proxy-1/content')
        && $request->hasHeader('Authorization', 'Bearer test-access-token'));
});

test('phone click recording proxy uses stored recording id', function () {
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://media.ringcentral.test/restapi/v1.0/account/*/recording/*/content*' => Http::response(
            'click-audio',
            200,
            ['Content-Type' => 'audio/mpeg']
        ),
    ]);

    $user = User::factory()->create();
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_call_id' => 'pc-call-1',
        'ringcentral_recording_id' => 'rec-click-1',
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks.recording', $click))
        ->assertOk()
        ->assertSee('click-audio', false);
});

test('recording id falls back to raw call log payload', function () {
    $call = RingCentralCall::query()->create([
        'ringcentral_call_id' => 'raw-rec-call',
        'direction' => 'Inbound',
        'started_at' => now(),
        'duration' => 10,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155550222',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155550222',
        'recording_id' => null,
        'raw' => [
            'recording' => ['id' => 'rec-from-raw'],
        ],
        'synced_at' => now(),
    ]);

    expect($call->resolvedRecordingId())->toBe('rec-from-raw')
        ->and(app(RingCentralCallLogService::class)->recordingIdFromRecord([
            'recording' => ['id' => 'abc'],
        ]))->toBe('abc');
});

test('recording id is taken from detailed call legs when top-level recording is missing', function () {
    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'legged-call-1',
                'sessionId' => 'session-legged',
                'startTime' => '2026-08-01T18:00:00.000Z',
                'duration' => 55,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '+14155550999'],
                'to' => ['phoneNumber' => '+16504614446'],
                'legs' => [[
                    'direction' => 'Inbound',
                    'recording' => ['id' => 'rec-from-leg'],
                ]],
            ]],
        ]),
    ]);

    CarbonImmutable::setTestNow('2026-08-01 19:00:00 UTC');
    app(RingCentralCallSyncService::class)->sync(forceDays: 1);

    $call = RingCentralCall::query()->where('ringcentral_call_id', 'legged-call-1')->sole();

    expect($call->recording_id)->toBe('rec-from-leg')
        ->and($call->hasRecording())->toBeTrue();
});
