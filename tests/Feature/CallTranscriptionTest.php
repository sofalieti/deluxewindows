<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\PromotionControl;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Services\CallTranscriptionProcessor;
use App\Services\CallTranscriptionQueue;
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
    config()->set('services.openai', [
        'api_key' => 'test-openai-key',
        'base_url' => 'https://api.openai.test/v1',
        'transcription_model' => 'gpt-4o-mini-transcribe',
        'summary_model' => 'gpt-5.4-nano',
        'transcript_min_duration_seconds' => 20,
        'transcript_stuck_minutes' => 15,
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

function makeCall(array $overrides = []): RingCentralCall
{
    return RingCentralCall::query()->create(array_merge([
        'ringcentral_call_id' => 'tx-'.uniqid(),
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-08-01T18:00:00Z'),
        'duration' => 300,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155550999',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155550999',
        'recording_id' => 'rec-tx-1',
        'synced_at' => CarbonImmutable::parse('2026-08-01T18:05:00Z'),
    ], $overrides));
}

test('unlinked calls are not queued for transcription', function () {
    $call = makeCall(['contact_id' => null]);

    $queued = app(CallTranscriptionQueue::class)->enqueueIfEligible($call);

    expect($queued)->toBeFalse()
        ->and($call->fresh()->transcript_status)->toBeNull();
});

test('calls linked to a contact are queued when they have a recording', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Tx Client',
        'phone' => '+14155550999',
    ]);
    $call = makeCall(['contact_id' => $contact->id]);

    $queued = app(CallTranscriptionQueue::class)->enqueueIfEligible($call);

    expect($queued)->toBeTrue()
        ->and($call->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);
});

test('calls linked via non-spam lead phone are queued', function () {
    Lead::query()->create([
        'full_name' => 'Lead Tx',
        'email' => 'lead-tx@example.com',
        'phone' => '+14155550999',
        'status' => Lead::STATUS_NEW,
    ]);
    $call = makeCall(['contact_id' => null, 'external_phone' => '+14155550999']);

    $queued = app(CallTranscriptionQueue::class)->enqueueIfEligible($call);

    expect($queued)->toBeTrue()
        ->and($call->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);
});

test('completed transcripts are not re-queued by sync', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Done Client',
        'phone' => '+14155550999',
    ]);
    $call = makeCall([
        'contact_id' => $contact->id,
        'transcript_status' => RingCentralCall::TRANSCRIPT_COMPLETED,
        'transcript' => 'Already done',
        'ringcentral_call_id' => 'sync-idempotent-1',
    ]);

    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'sync-idempotent-1',
                'sessionId' => 's1',
                'startTime' => '2026-08-01T18:00:00.000Z',
                'duration' => 300,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '+14155550999'],
                'to' => ['phoneNumber' => '+16504614446'],
                'recording' => ['id' => 'rec-tx-1'],
            ]],
        ]),
    ]);

    CarbonImmutable::setTestNow('2026-08-01 19:00:00 UTC');
    app(RingCentralCallSyncService::class)->sync(forceDays: 1);

    expect($call->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_COMPLETED)
        ->and($call->fresh()->transcript)->toBe('Already done');
});

test('manual force re-queues a completed transcript', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Force Client',
        'phone' => '+14155550999',
    ]);
    $call = makeCall([
        'contact_id' => $contact->id,
        'transcript_status' => RingCentralCall::TRANSCRIPT_COMPLETED,
        'transcript' => 'Old text',
    ]);

    $queued = app(CallTranscriptionQueue::class)->enqueue($call, force: true);

    expect($queued)->toBeTrue()
        ->and($call->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);
});

test('processor handles only one pending call and saves transcript plus summary', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Proc Client',
        'phone' => '+14155550999',
    ]);
    $first = makeCall([
        'contact_id' => $contact->id,
        'ringcentral_call_id' => 'proc-1',
        'recording_id' => 'rec-proc-1',
        'transcript_status' => RingCentralCall::TRANSCRIPT_PENDING,
        'transcript_queued_at' => now()->subMinutes(5),
    ]);
    $second = makeCall([
        'contact_id' => $contact->id,
        'ringcentral_call_id' => 'proc-2',
        'recording_id' => 'rec-proc-2',
        'external_phone' => '+14155550888',
        'from_phone' => '+14155550888',
        'transcript_status' => RingCentralCall::TRANSCRIPT_PENDING,
        'transcript_queued_at' => now()->subMinutes(1),
    ]);

    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'token',
            'expires_in' => 3600,
        ]),
        'https://media.ringcentral.test/restapi/v1.0/account/*/recording/*/content*' => Http::response(
            'fake-mp3-bytes',
            200,
            ['Content-Type' => 'audio/mpeg']
        ),
        'https://api.openai.test/v1/audio/transcriptions' => Http::response([
            'text' => 'Customer asked about vinyl windows and agreed to a Saturday measure.',
        ]),
        'https://api.openai.test/v1/chat/completions' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => json_encode([
                        'overview' => 'Customer wants vinyl windows and booked a measure.',
                        'agreements' => ['Saturday measure appointment'],
                        'next_steps' => ['Send confirmation email'],
                        'objections' => [],
                        'appointment' => 'Saturday morning',
                        'quote_discussed' => null,
                    ]),
                ],
            ]],
        ]),
    ]);

    $result = app(CallTranscriptionProcessor::class)->processNext();

    expect($result)->not->toBeNull()
        ->and($result['call_id'])->toBe($first->id)
        ->and($first->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_COMPLETED)
        ->and($first->fresh()->transcript)->toContain('vinyl windows')
        ->and($first->fresh()->transcript_summary['overview'])->toContain('vinyl windows')
        ->and($first->fresh()->transcript_summary['agreements'])->toContain('Saturday measure appointment')
        ->and($second->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);

    // Second tick would process the next one; while first is completed, next pending is free.
    $secondResult = app(CallTranscriptionProcessor::class)->processNext();
    expect($secondResult['call_id'])->toBe($second->id)
        ->and($second->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_COMPLETED);
});

test('orchid re-transcribe button queues a completed call', function () {
    $user = User::factory()->create();
    $user->forceFill(['permissions' => ['platform.leads' => true]])->save();

    $contact = Contact::query()->create([
        'full_name' => 'Ui Client',
        'phone' => '+14155550999',
    ]);
    $call = makeCall([
        'contact_id' => $contact->id,
        'transcript_status' => RingCentralCall::TRANSCRIPT_COMPLETED,
        'transcript' => 'Old',
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->post(route('platform.ringcentral-calls', ['method' => 'queueTranscript']), [
            'call' => $call->id,
        ])
        ->assertRedirect();

    expect($call->fresh()->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);
});

test('phone click match enqueues a journal call for transcription', function () {
    config()->set('services.openai.transcript_min_duration_seconds', 10);
    CarbonImmutable::setTestNow('2026-08-01 18:10:00 UTC');

    Http::fake([
        'https://platform.ringcentral.test/restapi/oauth/token' => Http::response([
            'access_token' => 'token',
            'expires_in' => 3600,
        ]),
        'https://platform.ringcentral.test/restapi/v1.0/account/*/call-log*' => Http::response([
            'records' => [[
                'id' => 'pc-tx-call',
                'sessionId' => 'sess',
                'telephonySessionId' => 'tel',
                'startTime' => '2026-08-01T18:08:00.000Z',
                'duration' => 90,
                'type' => 'Voice',
                'direction' => 'Inbound',
                'result' => 'Accepted',
                'from' => ['phoneNumber' => '+14155550777'],
                'to' => ['phoneNumber' => '+16504614446'],
                'recording' => ['id' => 'rec-pc-1'],
            ]],
        ]),
    ]);

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);
    $click->forceFill([
        'created_at' => CarbonImmutable::parse('2026-08-01 18:07:00 UTC'),
        'updated_at' => CarbonImmutable::parse('2026-08-01 18:07:00 UTC'),
    ])->saveQuietly();

    (new \App\Jobs\MatchPhoneClickToRingCentral($click->id))
        ->handle(app(\App\Services\RingCentralCallLogService::class));

    $call = RingCentralCall::query()->where('ringcentral_call_id', 'pc-tx-call')->sole();

    expect($click->refresh()->ringcentral_status)->toBe(PhoneClick::RINGCENTRAL_FOUND)
        ->and($call->recording_id)->toBe('rec-pc-1')
        ->and($call->transcript_status)->toBe(RingCentralCall::TRANSCRIPT_PENDING);
});
