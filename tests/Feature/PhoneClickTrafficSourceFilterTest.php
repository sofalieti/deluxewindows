<?php

use App\Models\PhoneClick;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('phone clicks list filters by traffic source', function () {
    $user = User::factory()->create();
    $user->forceFill([
        'permissions' => adminTrafficPermissions(),
    ])->save();

    PhoneClick::query()->create([
        'phone' => '+16501110001',
        'source_label' => 'header-phone',
        'page_url' => 'https://example.com/google-click',
        'gclid' => 'test-gclid',
    ]);

    PhoneClick::query()->create([
        'phone' => '+16501110002',
        'source_label' => 'header-phone',
        'page_url' => 'https://example.com/microsoft-click',
        'msclkid' => 'test-msclkid',
    ]);

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks'))
        ->assertOk()
        ->assertSee('All sources')
        ->assertSee('Google Ads')
        ->assertSee('Microsoft Ads')
        ->assertSee('+16501110001')
        ->assertSee('+16501110002');

    $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user)
        ->get(route('platform.phone-clicks', ['filter' => ['traffic_source' => 'microsoft_ads']]))
        ->assertOk()
        ->assertSee('+16501110002')
        ->assertDontSee('+16501110001');
});
