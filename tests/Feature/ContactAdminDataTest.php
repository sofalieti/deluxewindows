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

test('contact comments can be added and appear with lead comments in the timeline', function () {
    $user = User::factory()->create();
    $contact = Contact::query()->create([
        'full_name' => 'Comment Client',
        'email' => 'comment@example.com',
        'phone' => '6505558000',
    ]);
    $lead = Lead::query()->create([
        'contact_id' => $contact->id,
        'full_name' => 'Comment Client',
        'email' => 'comment@example.com',
        'phone' => '6505558000',
    ]);
    LeadComment::query()->create([
        'lead_id' => $lead->id,
        'user_id' => $user->id,
        'body' => 'Lead note',
        'created_at' => now()->subMinute(),
        'updated_at' => now()->subMinute(),
    ]);

    $this->actingAs($user);
    $request = Request::create('/admin/contacts/'.$contact->id.'/edit', 'POST', [
        'comment' => 'Contact-level note',
    ]);
    (new ContactEditScreen)->addComment($contact, $request);

    $timeline = $contact->fresh()->timelineComments();

    expect($contact->comments)->toHaveCount(1)
        ->and($timeline)->toHaveCount(2)
        ->and($timeline->first()->type)->toBe('contact')
        ->and($timeline->first()->body)->toBe('Contact-level note')
        ->and($timeline->last()->type)->toBe('lead')
        ->and($timeline->last()->lead_id)->toBe($lead->id);
});

test('leads list can be filtered by contact from the contacts list deep link', function () {
    $contact = Contact::query()->create([
        'full_name' => 'Filter Client',
        'email' => 'filter@example.com',
        'phone' => '6505557000',
    ]);
    $other = Contact::query()->create([
        'full_name' => 'Other Client',
        'email' => 'other-filter@example.com',
        'phone' => '6505557001',
    ]);
    $matching = Lead::query()->create([
        'contact_id' => $contact->id,
        'full_name' => 'Filter Client',
        'email' => 'filter@example.com',
        'phone' => '6505557000',
    ]);
    Lead::query()->create([
        'contact_id' => $other->id,
        'full_name' => 'Other Client',
        'email' => 'other-filter@example.com',
        'phone' => '6505557001',
    ]);

    request()->merge(['filter' => ['contact_id' => $contact->id]]);

    $screen = new \App\Orchid\Screens\Leads\LeadListScreen;
    $result = $screen->query();

    expect($result['contactFilter']->id)->toBe($contact->id)
        ->and($result['leads']->pluck('id')->all())->toBe([$matching->id])
        ->and($screen->name())->toBe('Leads for Filter Client');
});
