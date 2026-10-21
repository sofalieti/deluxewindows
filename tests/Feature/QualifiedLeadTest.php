<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\SiteVisit;
use App\Models\VisitsSetting;
use App\Services\VisitsSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('a qualified window count is stored and the lead id is returned', function () {
    $response = postJson('/contact-form', [
        'Name' => 'Ada Lovelace',
        'Phone' => '6505551212',
        'Email' => 'ada@example.com',
        'window_count' => '6-10',
        'opening_kind' => 'doors',
        'gbraid' => 'gb-live',
        'first_gbraid' => 'gb-first',
        'page_url' => 'https://www.deluxewindows.com/doors',
    ]);

    $response->assertOk()->assertJson(['ok' => true, 'spam' => false]);

    $lead = Lead::query()->sole();

    $response->assertJson(['id' => $lead->id]);

    expect($lead->meta['window_count'] ?? null)->toBe('6-10')
        ->and($lead->meta['gbraid'] ?? null)->toBe('gb-live')
        ->and($lead->meta['first_touch']['gbraid'] ?? null)->toBe('gb-first')
        ->and($lead->openingSummary())->toBe('Doors · 6–10')
        ->and($lead->trafficSourceKey())->toBe('google_ads');
});

test('a legacy opening count is still accepted without the new field', function () {
    postJson('/contact-form', [
        'Name' => 'Grace Hopper',
        'Phone' => '6505553434',
        'Email' => 'grace@example.com',
        'opening_count' => '1-3',
        'opening_kind' => 'windows',
    ])->assertOk()->assertJsonPath('spam', false);

    $lead = Lead::query()->sole();

    expect($lead->meta['window_count'] ?? null)->toBeNull()
        ->and($lead->openingSummary())->toBe('Windows · 1–3')
        ->and($lead->id)->toBeGreaterThan(0);
});

test('a lead without either quantity is rejected', function () {
    postJson('/contact-form', [
        'Name' => 'No Quantity',
        'Phone' => '6505559999',
        'Email' => 'none@example.com',
    ])->assertStatus(422);

    assertDatabaseCount('leads', 0);
});

test('an unknown window count is rejected', function () {
    postJson('/contact-form', [
        'Name' => 'Bad Quantity',
        'Phone' => '6505558888',
        'Email' => 'bad@example.com',
        'window_count' => '4-10',
    ])->assertStatus(422);

    assertDatabaseCount('leads', 0);
});

test('a visit stores gbraid and classifies it as Google Ads', function () {
    VisitsSetting::query()->updateOrCreate(
        ['scope' => 'default'],
        ['enabled' => true]
    );
    app(VisitsSettingsService::class)->forgetCache();

    postJson('/visit', [
        'page_url' => 'https://www.deluxewindows.com/',
        'landing_page' => '/',
        'gbraid' => 'gb-visit',
        'wbraid' => 'wb-visit',
        'first_wbraid' => 'wb-first',
    ])->assertOk();

    $visit = SiteVisit::query()->sole();

    expect($visit->gbraid)->toBe('gb-visit')
        ->and($visit->wbraid)->toBe('wb-visit')
        ->and($visit->first_wbraid)->toBe('wb-first')
        ->and($visit->traffic_source)->toBe('google_ads');
});
