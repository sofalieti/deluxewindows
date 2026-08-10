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
        'channel' => 'callback',
        'page_url' => 'https://www.deluxewindows.com/windows',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'bay-area',
        'msclkid' => 'ms-click-123',
        'first_msclkid' => 'ms-click-123',
        'landing_page' => '/',
    ])
        ->assertOk()
        ->assertJson(['ok' => true, 'spam' => false, 'channel' => 'callback']);

    assertDatabaseCount('leads', 1);

    $lead = Lead::query()->sole();

    expect($lead->phone)->toBe('(650) 555-1212')
        ->and($lead->full_name)->toBe('Callback request')
        ->and($lead->email)->toBe('callback+6505551212@noreply.deluxewindows.com')
        ->and($lead->message)->toBe('Bing Ads phone modal callback request')
        ->and($lead->utm_source)->toBe('bing')
        ->and($lead->meta['via'] ?? null)->toBe('bing-phone-callback')
        ->and($lead->meta['channel'] ?? null)->toBe('callback')
        ->and($lead->meta['form_id'] ?? null)->toBe('bing-phone-callback')
        ->and($lead->meta['msclkid'] ?? null)->toBe('ms-click-123')
        ->and($lead->status)->not->toBe(Lead::STATUS_SPAM);
});

test('sms channel stores customer phone for us to text them', function () {
    postJson('/callback-request', [
        'phone' => '6505559999',
        'channel' => 'sms',
        'utm_source' => 'bing',
        'msclkid' => 'ms-sms',
    ])
        ->assertOk()
        ->assertJson(['ok' => true, 'channel' => 'sms']);

    $lead = Lead::query()->sole();

    expect($lead->full_name)->toBe('SMS request')
        ->and($lead->phone)->toBe('6505559999')
        ->and($lead->email)->toBe('sms+6505559999@noreply.deluxewindows.com')
        ->and($lead->meta['via'] ?? null)->toBe('bing-phone-sms')
        ->and($lead->meta['channel'] ?? null)->toBe('sms')
        ->and($lead->message)->toContain('SMS');
});

test('whatsapp channel stores customer phone for us to message them', function () {
    postJson('/callback-request', [
        'phone' => '(415) 555-0001',
        'channel' => 'whatsapp',
        'utm_source' => 'bing',
        'msclkid' => 'ms-wa',
    ])
        ->assertOk()
        ->assertJson(['ok' => true, 'channel' => 'whatsapp']);

    $lead = Lead::query()->sole();

    expect($lead->full_name)->toBe('WhatsApp request')
        ->and($lead->phone)->toBe('(415) 555-0001')
        ->and($lead->email)->toBe('whatsapp+4155550001@noreply.deluxewindows.com')
        ->and($lead->meta['via'] ?? null)->toBe('bing-phone-whatsapp')
        ->and($lead->meta['channel'] ?? null)->toBe('whatsapp')
        ->and($lead->message)->toContain('WhatsApp');
});

test('callback request requires a phone number', function () {
    postJson('/callback-request', [
        'channel' => 'sms',
        'utm_source' => 'bing',
        'msclkid' => 'ms-no-phone',
    ])->assertStatus(422);

    assertDatabaseCount('leads', 0);
});
