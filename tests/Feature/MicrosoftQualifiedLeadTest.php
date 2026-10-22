<?php

use App\Jobs\SendLeadQualifiedToMicrosoftAds;
use App\Models\Lead;
use App\Models\PhoneClick;
use App\Models\User;
use App\Orchid\Screens\Leads\LeadEditScreen;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.microsoft_ads', [
        'developer_token' => 'ms-dev-token',
        'client_id' => 'ms-client',
        'client_secret' => 'ms-secret',
        'refresh_token' => 'ms-refresh',
        'customer_id' => '4242',
        'account_id' => '2424',
        'phone_conversion_name' => 'Phone Call Confirmed',
        'qualified_conversion_name' => 'Qualified Lead - 3+ Windows',
        'qualified_window_days' => 90,
        'identity_provider' => 'microsoft',
        'api_base_url' => 'https://campaign.bingads.test',
        'oauth_token_url' => 'https://login.test/token',
    ]);

    Cache::flush();

    $this->travelTo(
        CarbonImmutable::parse('2026-10-22 18:30:00 UTC')->setTimezone((string) config('app.timezone'))
    );
    $this->actingAs(User::factory()->create());
});

afterEach(function () {
    $this->travelBack();
});

function qualifiedLeadFixture(array $overrides = []): Lead
{
    return Lead::query()->create(array_merge([
        'full_name' => 'Pat Example',
        'email' => 'pat@example.com',
        'phone' => '(650) 555-0101',
        'status' => Lead::STATUS_NEW,
        'meta' => ['msclkid' => 'bing-click-abc123', 'window_count' => '6-10'],
    ], $overrides));
}

function staffSave(Lead $lead, array $qualification, string $status = Lead::STATUS_APPOINTMENT, array $leadOverrides = []): void
{
    $request = Request::create('/admin/leads/'.$lead->id.'/edit', 'POST', [
        'lead' => array_merge([
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'status' => $status,
        ], $leadOverrides),
        'qualification' => $qualification,
    ]);

    (new LeadEditScreen)->saveLead($lead, $request);
}

function threePlusHouse(array $overrides = []): array
{
    return array_merge([
        'window_count' => '3-4',
        'project_type' => 'replacement',
        'property_type' => 'house',
        'zip' => '94502',
        'decision_maker' => 'yes',
    ], $overrides);
}

function fakeMicrosoft(array $applyResponses = []): void
{
    $sequence = Http::sequence();
    foreach ($applyResponses ?: [['PartialErrors' => []]] as $body) {
        $sequence->push($body);
    }

    Http::fake([
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => $sequence,
    ]);
}

test('a 1-2 window lead moved to appointment does not qualify for Microsoft', function () {
    Queue::fake();
    $lead = qualifiedLeadFixture();

    staffSave($lead, threePlusHouse(['window_count' => '1-2']));

    $lead->refresh();
    expect($lead->status)->toBe(Lead::STATUS_APPOINTMENT)
        ->and($lead->qualification_window_count)->toBe('1-2')
        ->and($lead->bing_qualified_at)->toBeNull()
        ->and($lead->bing_qualified_status)->toBeNull();
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
});

test('a ZIP outside the service area or a non decision maker does not qualify', function (array $qualification) {
    Queue::fake();
    $lead = qualifiedLeadFixture();

    staffSave($lead, threePlusHouse($qualification));

    expect($lead->refresh()->bing_qualified_at)->toBeNull();
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
})->with([
    'outside zone' => [['zip' => '10001']],
    'not decision maker' => [['decision_maker' => 'no']],
    'apartment' => [['property_type' => 'apartment']],
    'doors only' => [['project_type' => 'doors_only']],
]);

test('a confirmed 3+ house in the service area is sent once with frozen values and no value', function () {
    $lead = qualifiedLeadFixture();
    Queue::fake();

    staffSave($lead, threePlusHouse(['window_count' => '10plus']));

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING)
        ->and($lead->bing_qualified_msclkid)->toBe('bing-click-abc123')
        ->and($lead->bing_qualified_conversion_name)->toBe('Qualified Lead - 3+ Windows')
        ->and($lead->bing_qualified_at->utc()->format('Y-m-d H:i:s'))->toBe('2026-10-22 18:30:00');
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);

    fakeMicrosoft();
    (new SendLeadQualifiedToMicrosoftAds($lead->id))->handle(app(MicrosoftAdsOfflineConversionService::class));

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SENT)
        ->and($lead->bing_qualified_sent_at)->not->toBeNull()
        ->and($lead->bing_qualified_error)->toBeNull();

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/OfflineConversions/Apply')) {
            return false;
        }
        $conversion = $request['OfflineConversions'][0];

        return $conversion === [
            'MicrosoftClickId' => 'bing-click-abc123',
            'ConversionName' => 'Qualified Lead - 3+ Windows',
            'ConversionTime' => '2026-10-22T18:30:00Z',
        ];
    });
});

test('re-saving the card does not create a new qualification', function () {
    Queue::fake();
    $lead = qualifiedLeadFixture();
    staffSave($lead, threePlusHouse());
    $firstQualifiedAt = $lead->refresh()->bing_qualified_at->toIso8601String();

    $this->travel(2)->hours();
    staffSave($lead->refresh(), threePlusHouse(['window_count' => '10plus']), Lead::STATUS_QUOTED, ['city' => 'Alameda']);
    staffSave($lead->refresh(), threePlusHouse(), Lead::STATUS_APPOINTMENT, ['city' => 'Oakland']);

    expect($lead->refresh()->bing_qualified_at->toIso8601String())->toBe($firstQualifiedAt);
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a retry after a partial error resends the identical conversion', function () {
    fakeMicrosoft([
        ['PartialErrors' => [['ErrorCode' => 'OfflineConversionMicrosoftClickIdInvalid', 'Code' => 3801, 'Message' => 'Temporary']]],
        ['PartialErrors' => []],
    ]);
    $lead = qualifiedLeadFixture();

    // Sync queue in tests: the first upload runs right after the staff save.
    staffSave($lead, threePlusHouse());
    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_FAILED)
        ->and($lead->bing_qualified_sent_at)->toBeNull()
        ->and($lead->bing_qualified_error)->toContain('3801');

    $this->travel(1)->hours();
    Artisan::call('ads:retry-bing-qualified', ['--sync' => true]);

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SENT)
        ->and($lead->bing_qualified_attempts)->toBe(2)
        ->and($lead->bing_qualified_error)->toBeNull();

    $payloads = collect(Http::recorded())
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/OfflineConversions/Apply'))
        ->map(fn ($pair) => $pair[0]['OfflineConversions'][0])
        ->values();
    expect($payloads)->toHaveCount(2)
        ->and($payloads[0])->toBe($payloads[1])
        ->and($payloads[0]['ConversionTime'])->toBe('2026-10-22T18:30:00Z');

    Artisan::call('ads:retry-bing-qualified', ['--sync' => true]);
    expect(collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), '/OfflineConversions/Apply')))
        ->toHaveCount(2);
});

test('a form and a call about the same project qualify only once', function () {
    Queue::fake();
    $formLead = qualifiedLeadFixture();
    staffSave($formLead, threePlusHouse());

    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'bing-click-abc123',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
    ]);
    $callLead = qualifiedLeadFixture([
        'email' => 'phone-click-'.$click->id.'@click.local',
        'meta' => ['via' => 'phone-click', 'phone_click_id' => $click->id],
    ]);
    staffSave($callLead, threePlusHouse());

    $callLead->refresh();
    expect($formLead->refresh()->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING)
        ->and($callLead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($callLead->bing_qualified_error)->toContain('lead #'.$formLead->id);
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a lead created from a confirmed call uses the call msclkid even after Phone Call Confirmed was sent', function () {
    Queue::fake();
    $click = PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'bing-call-click-9',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'bing_ads_conversion_sent_at' => now()->subHour(),
    ]);
    $lead = qualifiedLeadFixture([
        'email' => 'phone-click-'.$click->id.'@click.local',
        'meta' => ['via' => 'phone-click', 'phone_click_id' => $click->id],
    ]);

    staffSave($lead, threePlusHouse());

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING)
        ->and($lead->bing_qualified_msclkid)->toBe('bing-call-click-9');
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a lead without msclkid is skipped for Bing with a reason', function () {
    Queue::fake();
    $lead = qualifiedLeadFixture(['meta' => ['gclid' => 'google-click-1']]);

    staffSave($lead, threePlusHouse());

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($lead->bing_qualified_error)->toContain('No MSCLKID');
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
});

test('a qualification outside the conversion window is skipped', function () {
    Queue::fake();
    $lead = qualifiedLeadFixture();
    $lead->created_at = now()->subDays(120);
    $lead->save();

    staffSave($lead, threePlusHouse());

    $lead->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($lead->bing_qualified_error)->toContain('conversion window');
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
});
