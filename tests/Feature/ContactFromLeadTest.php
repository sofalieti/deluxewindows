<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use App\Services\ContactFromLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

test('creating a contact from a lead copies identity and links matching non spam leads', function () {
    $user = User::factory()->create();
    $source = Lead::query()->create([
        'full_name' => 'Jane Client',
        'email' => 'Jane@Example.com',
        'phone' => '(650) 555-1000',
        'city' => 'San Mateo',
        'message' => 'First request',
    ]);
    $matching = Lead::query()->create([
        'full_name' => 'Jane Client',
        'email' => 'jane@example.com',
        'phone' => '650-555-9999',
        'city' => 'Burlingame',
    ]);
    $spam = Lead::query()->create([
        'full_name' => 'Spam',
        'email' => 'jane@example.com',
        'phone' => '650-555-1000',
        'status' => Lead::STATUS_SPAM,
    ]);

    $contact = app(ContactFromLeadService::class)->createOrAttach($source, $user->id);

    expect($contact->full_name)->toBe('Jane Client')
        ->and($contact->email)->toBe('Jane@Example.com')
        ->and($contact->phone)->toBe('(650) 555-1000')
        ->and($contact->city)->toBe('San Mateo')
        ->and($contact->source_lead_id)->toBe($source->id)
        ->and($contact->created_by_user_id)->toBe($user->id)
        ->and($source->refresh()->contact_id)->toBe($contact->id)
        ->and($matching->refresh()->contact_id)->toBe($contact->id)
        ->and($spam->refresh()->contact_id)->toBeNull()
        ->and($source->changes()->where('field', 'contact_id')->exists())->toBeTrue();
});

test('a future lead attaches to the one matching contact without overwriting it', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Original Name',
        'email' => 'client@example.com',
        'phone' => '6505551000',
        'city' => 'Original City',
    ]);
    $lead = Lead::query()->create([
        'full_name' => 'Different Submission Name',
        'email' => 'CLIENT@example.com',
        'phone' => '6505552000',
        'city' => 'Different City',
    ]);

    app(ContactFromLeadService::class)->attachNewLead($lead);

    expect($lead->refresh()->contact_id)->toBe($contact->id)
        ->and($contact->refresh()->full_name)->toBe('Original Name')
        ->and($contact->city)->toBe('Original City');
});

test('conflicting email and phone matches are not merged automatically', function () {
    Contact::query()->create([
        'full_name' => 'Email Match',
        'email' => 'client@example.com',
        'phone' => '6505551000',
    ]);
    Contact::query()->create([
        'full_name' => 'Phone Match',
        'email' => 'other@example.com',
        'phone' => '6505552000',
    ]);
    $lead = Lead::query()->create([
        'full_name' => 'Ambiguous Client',
        'email' => 'client@example.com',
        'phone' => '6505552000',
    ]);

    expect(app(ContactFromLeadService::class)->attachNewLead($lead))->toBeNull()
        ->and($lead->refresh()->contact_id)->toBeNull();

    $created = app(ContactFromLeadService::class)->createOrAttach($lead);

    expect(Contact::query()->count())->toBe(3)
        ->and($lead->refresh()->contact_id)->toBe($created->id);
});

test('creating from an already linked lead is idempotent', function () {
    $lead = Lead::query()->create([
        'full_name' => 'Repeat Client',
        'email' => 'repeat@example.com',
        'phone' => '6505553000',
    ]);
    $service = app(ContactFromLeadService::class);

    $first = $service->createOrAttach($lead);
    $second = $service->createOrAttach($lead->refresh());

    expect($second->id)->toBe($first->id)
        ->and(Contact::query()->count())->toBe(1);
});

test('orchid create contact action resolves the service and redirects to the contact', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => array_merge(adminTrafficPermissions(phoneClicks: false), [
            'platform.contacts' => true,
        ]),
    ])->save();
    $lead = Lead::query()->create([
        'full_name' => 'Orchid Client',
        'email' => 'orchid@example.com',
        'phone' => '6505556000',
    ]);

    $response = $this->withoutMiddleware(Access::class)->actingAs($user)->post(route('platform.leads.edit', [
        'lead' => $lead,
        'method' => 'createContact',
    ]));

    expect($response->status())->toBe(302);
    $contact = Contact::query()->sole();
    $response->assertRedirect(route('platform.contacts.edit', $contact));
    expect($lead->refresh()->contact_id)->toBe($contact->id);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.contacts.edit', $contact))
        ->assertOk();

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.contacts.create'))
        ->assertOk();
});
