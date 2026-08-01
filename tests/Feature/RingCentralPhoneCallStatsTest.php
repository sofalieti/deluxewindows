<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Services\RingCentralPhoneCallStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('phone call stats count inbound outbound and connected results', function () {
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'stats-in-1',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T18:00:00Z'),
        'duration' => 95,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505551212',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505551212',
        'result' => 'Accepted',
        'synced_at' => now(),
    ]);
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'stats-out-1',
        'direction' => 'Outbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T19:00:00Z'),
        'duration' => 12,
        'business_phone' => '+16504614446',
        'from_phone' => '+16504614446',
        'to_phone' => '+16505551212',
        'external_phone' => '+16505551212',
        'result' => 'Missed',
        'synced_at' => now(),
    ]);
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'stats-other',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T20:00:00Z'),
        'duration' => 40,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155559999',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155559999',
        'result' => 'Accepted',
        'synced_at' => now(),
    ]);

    $stats = app(RingCentralPhoneCallStatsService::class)->statsForPhone('(650) 555-1212');

    expect($stats['inbound'])->toBe(1)
        ->and($stats['outbound'])->toBe(1)
        ->and($stats['connected'])->toBeTrue()
        ->and($stats['connected_count'])->toBe(1);
});

test('leads list shows call stats instead of email column', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();

    Lead::query()->create([
        'full_name' => 'Stats Lead',
        'phone' => '6505553434',
        'email' => 'stats-lead@example.com',
        'status' => Lead::STATUS_NEW,
    ]);

    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'lead-list-call',
        'direction' => 'Outbound',
        'started_at' => CarbonImmutable::parse('2026-07-31T16:00:00Z'),
        'duration' => 80,
        'business_phone' => '+16504614446',
        'from_phone' => '+16504614446',
        'to_phone' => '+16505553434',
        'external_phone' => '+16505553434',
        'result' => 'Call connected',
        'synced_at' => now(),
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.leads'))
        ->assertOk()
        ->assertSee('stats-lead@example.com')
        ->assertSee('↑ 1')
        ->assertSee('Connected')
        ->assertDontSee('>Email</');
});

test('contacts list shows call stats instead of email column', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.contacts' => true],
    ])->save();

    Contact::query()->create([
        'full_name' => 'Stats Contact',
        'phone' => '(650) 555-5656',
        'email' => 'stats-contact@example.com',
    ]);

    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'contact-list-call',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-31T17:00:00Z'),
        'duration' => 5,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505555656',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505555656',
        'result' => 'Missed',
        'synced_at' => now(),
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.contacts'))
        ->assertOk()
        ->assertSee('stats-contact@example.com')
        ->assertSee('↓ 1')
        ->assertSee('No connect');
});
