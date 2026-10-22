<?php

use App\Models\PhoneClick;
use App\Services\MicrosoftPhoneClickRecheck;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
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
        'match_window_seconds' => 60,
        'match_window_seconds_microsoft' => 180,
        'lookup_window_minutes' => 10,
        'clock_tolerance_seconds' => 30,
        'retry_delay_seconds' => 120,
    ]);
    Cache::flush();
    Queue::fake();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('a microsoft ads call that starts two minutes after the tap is still matched', function () {
    CarbonImmutable::setTestNow('2026-10-22 16:10:00 UTC');
    $click = microsoftClick('2026-10-22 16:00:00 UTC', PhoneClick::RINGCENTRAL_NO_CALL);

    fakeMicrosoftCallLog([[
        'id' => 'ms-call-1',
        'sessionId' => 'ms-session-1',
        'startTime' => '2026-10-22T16:02:00.000Z',
        'duration' => 90,
        'type' => 'Voice',
        'direction' => 'Inbound',
        'result' => 'Accepted',
        'from' => ['phoneNumber' => '+14155550123'],
        'to' => ['phoneNumber' => '+16504614446'],
    ]]);

    $result = app(MicrosoftPhoneClickRecheck::class)->run();

    expect($result['matched'])->toBe(1)
        ->and($click->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($click->ringcentral_from_phone)->toBe('+14155550123');
});

test('a non-microsoft call two minutes after the tap is still outside the window', function () {
    CarbonImmutable::setTestNow('2026-10-22 16:10:00 UTC');
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-10-22 16:00:00 UTC'),
    ])->saveQuietly();

    fakeMicrosoftCallLog([[
        'id' => 'late-google-call',
        'sessionId' => 'late-google-session',
        'startTime' => '2026-10-22T16:02:00.000Z',
        'duration' => 40,
        'type' => 'Voice',
        'direction' => 'Inbound',
        'result' => 'Accepted',
        'from' => ['phoneNumber' => '+14155550123'],
        'to' => ['phoneNumber' => '+16504614446'],
    ]]);

    (new \App\Jobs\MatchPhoneClickToRingCentral($click->id))->handle(app(\App\Services\RingCentralCallLogService::class));

    expect($click->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_NO_CALL)
        ->and($click->ringcentral_call_id)->toBeNull();
});

test('pending clicks whose lookup window has ended do not stay on checking', function () {
    CarbonImmutable::setTestNow('2026-10-22 16:30:00 UTC');
    $click = microsoftClick('2026-10-22 16:00:00 UTC', PhoneClick::RINGCENTRAL_PENDING);
    fakeMicrosoftCallLog([]);

    Artisan::call('calls:match-pending');

    expect($click->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_NO_CALL);
});

function microsoftClick(string $createdAt, string $status): PhoneClick
{
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'msclk-test',
        'ringcentral_status' => $status,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse($createdAt),
    ])->saveQuietly();

    return $click->refresh();
}

function fakeMicrosoftCallLog(array $records): void
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
