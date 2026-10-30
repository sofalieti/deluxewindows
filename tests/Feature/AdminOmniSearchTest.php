<?php

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\RingCentralCall;
use App\Models\User;
use App\Services\TrafficSourceVisibility;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Http\Middleware\Access;

uses(RefreshDatabase::class);

function omniSearchUser(array $extra = []): User
{
    $permissions = [
        'platform.index' => true,
        'platform.leads' => true,
        'platform.contacts' => true,
        'platform.phone-clicks' => true,
    ];

    foreach (['leads', 'phone-clicks'] as $section) {
        foreach (TrafficSourceVisibility::BUCKETS as $bucket) {
            $permissions[TrafficSourceVisibility::permission($section, $bucket)] = true;
        }
    }

    $user = User::factory()->create();
    $user->forceFill(['permissions' => array_merge($permissions, $extra)])->save();

    return $user;
}

test('the admin header search suggests leads contacts phones and client calls', function () {
    $user = omniSearchUser();

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
        'phone' => '(650) 461-4446',
        'email' => 'bea@example.com',
    ]);
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/windows',
    ]);
    RingCentralCall::query()->create([
        'ringcentral_call_id' => 'omni-call',
        'direction' => 'Inbound',
        'result' => 'Accepted',
        'started_at' => CarbonImmutable::parse('2026-07-31T18:00:00Z'),
        'duration' => 20,
        'business_phone' => '+16504614446',
        'from_phone' => '+16504614446',
        'to_phone' => '+16504614446',
        'external_phone' => '+16504614446',
        'contact_id' => $contact->id,
        'synced_at' => now(),
    ]);

    $response = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->getJson(route('platform.omni-search', ['q' => '(650) 461-4446']));

    $response->assertOk();
    $titles = collect($response->json('groups'))
        ->flatMap(fn (array $group) => collect($group['items'])->pluck('title'))
        ->all();

    expect($titles)->toContain('Ada Window')
        ->and($titles)->toContain('Bea Client')
        ->and($titles)->toContain('+16504614446')
        ->and($titles)->not->toContain('Other Person');

    $call = collect($response->json('groups'))
        ->firstWhere('label', 'RingCentral calls');
    expect($call['items'][0]['badge'])->toBe('Client');

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.main'))
        ->assertOk()
        ->assertSee('dw-omni-search-input', false)
        ->assertSee('data-admin-search="jump"', false)
        ->assertSee('admin/search.js', false);

    $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->get(route('platform.leads'))
        ->assertOk()
        ->assertSee('data-admin-search="filter"', false)
        ->assertSee('form="dw-admin-search-form"', false)
        ->assertSee('data-admin-search-apply', false)
        ->assertDontSee('<form method="get"', false);
});

test('header search hides sections the user cannot open', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => [
            'platform.leads' => true,
            'platform.leads.source.direct' => true,
            'platform.leads.source.other' => true,
        ],
    ])->save();

    Contact::query()->create([
        'full_name' => 'Hidden Client',
        'phone' => '6502223333',
        'email' => 'hidden@example.com',
    ]);

    $response = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->getJson(route('platform.omni-search', ['q' => 'Hidden Client']));

    $response->assertOk();
    expect(collect($response->json('groups'))->pluck('label')->all())->not->toContain('Contacts');
});

test('header search completes a name and a phone that already exist', function () {
    $user = omniSearchUser();

    Lead::query()->create([
        'full_name' => 'Ada Window',
        'phone' => '(650) 461-4446',
        'email' => 'ada@example.com',
    ]);
    Lead::query()->create([
        'full_name' => 'Other Person',
        'phone' => '4155550100',
        'email' => 'other@example.com',
    ]);

    $name = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->getJson(route('platform.omni-search', ['q' => 'Ada']));

    $name->assertOk();
    expect($name->json('completion'))->toBe('Ada Window')
        ->and(collect($name->json('suggestions'))->pluck('value')->all())->toContain('Ada Window')
        ->and(collect($name->json('suggestions'))->pluck('value')->all())->not->toContain('Other Person');

    $phone = $this->withoutMiddleware(Access::class)
        ->actingAs($user)
        ->getJson(route('platform.omni-search', ['q' => '650']));

    $phone->assertOk();
    expect(collect($phone->json('suggestions'))->firstWhere('kind', 'Phone')['value'] ?? null)
        ->toBe('(650) 461-4446');
});
