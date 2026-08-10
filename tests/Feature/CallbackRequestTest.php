<?php

declare(strict_types=1);

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('callback request creates a bing phone callback lead', function () {
    postJson('/callback-request', [
        'phone' => '(650) 555-1212',
        'page_url' => 'https://www.deluxewindows.com/windows',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'bay-area',
        'msclkid' => 'ms-click-123',
        'first_msclkid' => 'ms-click-123',
        'landing_page' => '/',
    ])
        ->assertOk()
        ->assertJson(['ok' => true, 'spam' => false]);

    assertDatabaseCount('leads', 1);

    $lead = Lead::query()->sole();

    expect($lead->phone)->toBe('(650) 555-1212')
        ->and($lead->full_name)->toBe('Callback request')
        ->and($lead->email)->toBe('callback+6505551212@noreply.deluxewindows.com')
        ->and($lead->message)->toBe('Bing Ads phone modal callback request')
        ->and($lead->utm_source)->toBe('bing')
        ->and($lead->meta['via'] ?? null)->toBe('bing-phone-callback')
        ->and($lead->meta['form_id'] ?? null)->toBe('bing-phone-callback')
        ->and($lead->meta['msclkid'] ?? null)->toBe('ms-click-123')
        ->and($lead->status)->not->toBe(Lead::STATUS_SPAM);
});

test('callback request requires a phone number', function () {
    postJson('/callback-request', [
        'utm_source' => 'bing',
        'msclkid' => 'ms-no-phone',
    ])->assertStatus(422);

    assertDatabaseCount('leads', 0);
});
