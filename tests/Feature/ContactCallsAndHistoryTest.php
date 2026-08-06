<?php

use App\Models\Contact;
use App\Models\ContactChange;
use App\Models\Lead;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Orchid\Screens\Contacts\ContactEditScreen;
use App\Services\ContactFromLeadService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('contact save records field change history', function () {
    $user = User::factory()->create();
    $contact = Contact::query()->create([
        'full_name' => 'History Client',
        'phone' => '(650) 555-1000',
        'city' => 'San Mateo',
        'created_by_user_id' => $user->id,
    ]);

    $this->actingAs($user);
    (new ContactEditScreen)->save(
        $contact,
        Request::create('/admin/contacts/'.$contact->id.'/edit', 'POST', [
            'contact' => [
                'full_name' => 'History Client',
                'phone' => '(650) 555-1000',
                'email' => null,
                'city' => 'Palo Alto',
                'address' => null,
                'additional_information' => 'VIP',
            ],
        ]),
        app(ContactFromLeadService::class),
    );

    $changes = ContactChange::query()->where('contact_id', $contact->id)->orderBy('id')->get();
    expect($changes)->toHaveCount(2)
        ->and($changes->pluck('field')->all())->toBe(['city', 'additional_information'])
        ->and($changes->firstWhere('field', 'city')?->old_value)->toBe('San Mateo')
        ->and($changes->firstWhere('field', 'city')?->new_value)->toBe('Palo Alto');
});

test('contact lists ringcentral calls for the contact phone number', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Call Client',
        'phone' => '(650) 555-2222',
    ]);

    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'call-match-1',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T18:00:00Z'),
        'duration' => 95,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505552222',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505552222',
        'result' => 'Accepted',
        'synced_at' => CarbonImmutable::parse('2026-07-30T18:05:00Z'),
    ]);
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'call-other',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T19:00:00Z'),
        'duration' => 40,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155559999',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155559999',
        'result' => 'Missed',
        'synced_at' => CarbonImmutable::parse('2026-07-30T19:05:00Z'),
    ]);
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'call-outbound',
        'direction' => 'Outbound',
        'started_at' => CarbonImmutable::parse('2026-07-30T20:00:00Z'),
        'duration' => 120,
        'business_phone' => '+16504614446',
        'from_phone' => '+16504614446',
        'to_phone' => '+16505552222',
        'external_phone' => '+16505552222',
        'result' => 'Call connected',
        'synced_at' => CarbonImmutable::parse('2026-07-30T20:05:00Z'),
    ]);

    $calls = $contact->ringCentralCallsForPhone();

    expect($calls)->toHaveCount(2)
        ->and($calls->pluck('ringcentral_call_id')->all())->toBe(['call-outbound', 'call-match-1']);
});

test('contact edit screen exposes calls and history tabs', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.contacts' => true],
    ])->save();

    $contact = Contact::query()->create([
        'full_name' => 'Tab Client',
        'phone' => '+16505553333',
        'created_by_user_id' => $user->id,
    ]);
    ContactChange::record($contact, 'created', null, null, 'Contact created', $user->id);

    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'tab-call',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-31T16:00:00Z'),
        'duration' => 30,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505553333',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505553333',
        'recording_id' => 'rec-tab-1',
        'result' => 'Accepted',
        'synced_at' => CarbonImmutable::parse('2026-07-31T16:05:00Z'),
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.contacts.edit', $contact))
        ->assertOk()
        ->assertSee('Calls')
        ->assertSee('History')
        ->assertSee('Contact created')
        ->assertSee('Inbound')
        ->assertSee('+16505553333')
        ->assertSee('Listen')
        ->assertSee(route('platform.ringcentral-calls.recording', RingCentralCall::query()->where('ringcentral_call_id', 'tab-call')->sole()), false);
});

test('lead edit screen exposes calls tab with recording link', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => adminTrafficPermissions(phoneClicks: false),
    ])->save();

    $lead = Lead::query()->create([
        'full_name' => 'Lead Caller',
        'email' => 'lead-caller@example.com',
        'phone' => '+16505554444',
        'status' => Lead::STATUS_NEW,
    ]);

    $call = RingCentralCall::query()->create([
        'ringcentral_call_id' => 'lead-tab-call',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-31T16:00:00Z'),
        'duration' => 40,
        'business_phone' => '+16504614446',
        'from_phone' => '+16505554444',
        'to_phone' => '+16504614446',
        'external_phone' => '+16505554444',
        'recording_id' => 'rec-lead-1',
        'result' => 'Accepted',
        'synced_at' => CarbonImmutable::parse('2026-07-31T16:05:00Z'),
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.leads.edit', $lead))
        ->assertOk()
        ->assertSee('Calls')
        ->assertSee('Listen')
        ->assertSee(route('platform.ringcentral-calls.recording', $call), false);
});
