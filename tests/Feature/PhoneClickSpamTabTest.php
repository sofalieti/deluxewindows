<?php

use App\Models\PhoneClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('phone clicks spam tab and mark as spam work', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'source_label' => 'header-phone',
        'page_url' => 'https://example.com/doors',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks'))
        ->assertOk();

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->post(route('platform.phone-clicks', ['method' => 'markAsSpam']), [
            'phone_click_id' => $click->id,
        ])
        ->assertRedirect();

    expect($click->refresh()->isSpam())->toBeTrue();

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks'))
        ->assertOk()
        ->assertSee('Spam')
        ->assertSee('Restore');
});

test('leads spam tab renders', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => ['platform.leads' => true],
    ])->save();

    \App\Models\Lead::query()->create([
        'full_name' => 'Spam Person',
        'phone' => '5551112222',
        'email' => 'spam@example.com',
        'status' => \App\Models\Lead::STATUS_SPAM,
        'meta' => ['spam_reason' => 'honeypot'],
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.leads'))
        ->assertOk()
        ->assertSee('Spam')
        ->assertSee('honeypot')
        ->assertSee('Spam Person');
});
