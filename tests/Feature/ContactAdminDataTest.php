<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Models\LeadComment;
use App\Models\User;
use App\Orchid\Screens\Contacts\ContactEditScreen;
use App\Services\ContactFromLeadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('manual contact creation saves address and additional information and links matches', function () {
    $user = User::factory()->create();
    $lead = Lead::query()->create([
        'full_name' => 'Manual Client',
        'email' => 'manual@example.com',
        'phone' => '6505554000',
    ]);
    $request = Request::create('/admin/contacts/create', 'POST', [
        'contact' => [
            'full_name' => 'Manual Client',
            'email' => 'manual@example.com',
            'phone' => '(650) 555-4000',
            'city' => 'San Jose',
            'address' => '123 Main Street',
            'additional_information' => 'Prefers afternoon appointments.',
        ],
    ]);

    $this->actingAs($user);
    (new ContactEditScreen)->save(
        new Contact,
        $request,
        app(ContactFromLeadService::class),
    );

    $contact = Contact::query()->sole();
    expect($contact->address)->toBe('123 Main Street')
        ->and($contact->additional_information)->toBe('Prefers afternoon appointments.')
        ->and($contact->created_by_user_id)->toBe($user->id)
        ->and($lead->refresh()->contact_id)->toBe($contact->id);
});

test('contact aggregates traffic sources and comments retain their lead attribution', function () {
    $user = User::factory()->create();
    $contact = Contact::query()->create([
        'full_name' => 'Multi Lead Client',
        'email' => 'multi@example.com',
        'phone' => '6505555000',
    ]);
    $direct = Lead::query()->create([
        'contact_id' => $contact->id,
        'full_name' => 'Multi Lead Client',
        'email' => 'multi@example.com',
        'phone' => '6505555000',
    ]);
    $ads = Lead::query()->create([
        'contact_id' => $contact->id,
        'full_name' => 'Multi Lead Client',
        'email' => 'multi@example.com',
        'phone' => '6505555000',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'meta' => ['gclid' => 'test-click'],
    ]);
    LeadComment::query()->create([
        'lead_id' => $direct->id,
        'user_id' => $user->id,
        'body' => 'Comment on direct lead',
    ]);
    LeadComment::query()->create([
        'lead_id' => $ads->id,
        'user_id' => $user->id,
        'body' => 'Comment on paid lead',
    ]);

    $summary = collect($contact->fresh('leads')->trafficSummary())->keyBy('key');
    $comments = $contact->leadComments()->with('lead')->get();

    expect($summary['direct']['count'])->toBe(1)
        ->and($summary['google_ads']['count'])->toBe(1)
        ->and($comments)->toHaveCount(2)
        ->and($comments->pluck('lead_id')->sort()->values()->all())->toBe([$direct->id, $ads->id]);
});
