<?php

use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

test('smart search finds leads contacts and phone clicks by phone digits or name', function () {
    Lead::query()->create([
        'full_name' => 'Ada Window',
        'phone' => '(650) 461-4446',
        'email' => 'ada@example.com',
        'city' => 'Burlingame',
    ]);
    Lead::query()->create([
        'full_name' => 'Other Person',
        'phone' => '4155550100',
        'email' => 'other@example.com',
    ]);

    $contact = Contact::query()->create([
        'full_name' => 'Bea Client',
        'phone' => '650-222-3333',
        'email' => 'bea@example.com',
        'city' => 'San Mateo',
    ]);
    ContactEmail::query()->create([
        'contact_id' => $contact->id,
        'email' => 'bea.alt@example.com',
        'is_primary' => false,
    ]);

    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/windows',
        'utm_city' => 'Burlingame',
    ]);
    PhoneClick::query()->create([
        'phone' => '+14155550100',
        'page_url' => 'https://www.deluxewindows.com/',
    ]);

    request()->merge(['q' => '(650) 461-4446']);
    expect(Lead::query()->matchingSearch()->pluck('full_name')->all())->toBe(['Ada Window'])
        ->and(PhoneClick::query()->matchingSearch()->count())->toBe(1);

    request()->merge(['q' => 'bea.alt@example.com']);
    expect(Contact::query()->matchingSearch()->pluck('full_name')->all())->toBe(['Bea Client']);

    request()->merge(['q' => 'San Mateo']);
    expect(Contact::query()->matchingSearch()->pluck('full_name')->all())->toBe(['Bea Client']);
});

test('a RingCentral caller who matches a contact is marked as a client', function () {
    $user = User::factory()->create();
    $user->forceFill(['permissions' => ['platform.leads' => true]])->save();

    Contact::query()->create([
        'full_name' => 'Known Client',
        'phone' => '(415) 555-0001',
    ]);
    Lead::query()->create([
        'full_name' => 'Known Lead',
        'phone' => '415-555-0001',
        'email' => 'known-lead@example.com',
    ]);

    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'client-call',
        'direction' => 'Inbound',
        'started_at' => CarbonImmutable::parse('2026-07-31T18:00:00Z'),
        'duration' => 30,
        'business_phone' => '+16504614446',
        'from_phone' => '+14155550001',
        'to_phone' => '+16504614446',
        'external_phone' => '+14155550001',
        'synced_at' => now(),
    ]);

    app(\App\Services\RingCentralContactBinder::class)->backfillLinks();

    $call = RingCentralCall::query()->where('ringcentral_call_id', 'client-call')->sole();
    expect($call->isClient())->toBeTrue()
        ->and($call->lead_id)->not->toBeNull();

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.ringcentral-calls'))
        ->assertOk()
        ->assertSee('Client')
        ->assertSee('Known Client')
        ->assertSee('Known Lead');
});
