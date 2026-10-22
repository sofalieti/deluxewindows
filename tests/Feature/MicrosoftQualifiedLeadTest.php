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

use function Pest\Laravel\postJson;

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
    config()->set('services.lead_notifications.to', []);

    Cache::flush();

    $this->travelTo(
        CarbonImmutable::parse('2026-10-22 18:30:00 UTC')->setTimezone((string) config('app.timezone'))
    );
});

afterEach(function () {
    $this->travelBack();
});

function submitLeadForm(array $overrides = []): Lead
{
    postJson('/contact-form', array_merge([
        'Name' => 'Pat Example',
        'Phone' => '6505550101',
        'Email' => 'pat@example.com',
        'window_count' => '6-10',
        'opening_kind' => 'windows',
        'msclkid' => 'bing-click-abc123',
        'page_url' => 'https://www.deluxewindows.com/',
    ], $overrides))->assertOk()->assertJsonPath('spam', false);

    return Lead::query()->latest('id')->firstOrFail();
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

function applyPayloads(): \Illuminate\Support\Collection
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains($pair[0]->url(), '/OfflineConversions/Apply'))
        ->map(fn ($pair) => $pair[0]['OfflineConversions'][0])
        ->values();
}

test('a visitor who picks fewer than 3 windows or not sure is not qualified', function (string $count) {
    Queue::fake();

    $lead = submitLeadForm(['window_count' => $count]);

    expect($lead->refresh()->bing_qualified_at)->toBeNull()
        ->and($lead->bing_qualified_status)->toBeNull();
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
})->with(['1', '2', 'unsure']);

test('a visitor who picks 3+ windows is queued at submit time with frozen values', function (string $count) {
    Queue::fake();

    $lead = submitLeadForm(['window_count' => $count])->refresh();

    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING)
        ->and($lead->bing_qualified_msclkid)->toBe('bing-click-abc123')
        ->and($lead->bing_qualified_conversion_name)->toBe('Qualified Lead - 3+ Windows')
        ->and($lead->bing_qualified_at->utc()->format('Y-m-d H:i:s'))->toBe('2026-10-22 18:30:00');
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
})->with(['3-5', '6-10', '11plus']);

test('the upload sends only click id, goal name and time, without a value', function () {
    fakeMicrosoft();

    $lead = submitLeadForm()->refresh();

    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SENT)
        ->and($lead->bing_qualified_sent_at)->not->toBeNull()
        ->and(applyPayloads()->all())->toBe([[
            'MicrosoftClickId' => 'bing-click-abc123',
            'ConversionName' => 'Qualified Lead - 3+ Windows',
            'ConversionTime' => '2026-10-22T18:30:00Z',
        ]]);
});

test('a later staff save of the card does not qualify the lead again', function () {
    Queue::fake();
    $lead = submitLeadForm();
    $firstQualifiedAt = $lead->refresh()->bing_qualified_at->toIso8601String();

    $this->travel(2)->hours();
    $this->actingAs(User::factory()->create());
    (new LeadEditScreen)->saveLead($lead, Request::create('/admin/leads/'.$lead->id.'/edit', 'POST', [
        'lead' => [
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'city' => 'Alameda',
            'status' => Lead::STATUS_APPOINTMENT,
        ],
    ]));

    expect($lead->refresh()->bing_qualified_at->toIso8601String())->toBe($firstQualifiedAt);
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a retry after a partial error resends the identical conversion once', function () {
    fakeMicrosoft([
        ['PartialErrors' => [['ErrorCode' => 'OfflineConversionMicrosoftClickIdInvalid', 'Code' => 3801, 'Message' => 'Temporary']]],
        ['PartialErrors' => []],
    ]);

    $lead = submitLeadForm()->refresh();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_FAILED)
        ->and($lead->bing_qualified_error)->toContain('3801');

    $this->travel(1)->hours();
    Artisan::call('ads:retry-bing-qualified', ['--sync' => true]);
    Artisan::call('ads:retry-bing-qualified', ['--sync' => true]);

    $lead->refresh();
    $payloads = applyPayloads();
    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SENT)
        ->and($lead->bing_qualified_attempts)->toBe(2)
        ->and($payloads)->toHaveCount(2)
        ->and($payloads[0])->toBe($payloads[1]);
});

test('two submissions about the same project qualify only once', function () {
    Queue::fake();
    $first = submitLeadForm();
    $second = submitLeadForm(['window_count' => '11plus', 'Name' => 'Pat Example Again']);

    expect($first->refresh()->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING)
        ->and($second->refresh()->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($second->bing_qualified_error)->toContain('lead #'.$first->id);
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a confirmed phone call on the same click does not block the qualified conversion', function () {
    Queue::fake();
    PhoneClick::query()->create([
        'phone' => '+16504614446',
        'msclkid' => 'bing-click-abc123',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'bing_ads_conversion_sent_at' => now()->subHour(),
    ]);

    $lead = submitLeadForm()->refresh();

    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_PENDING);
    Queue::assertPushed(SendLeadQualifiedToMicrosoftAds::class, 1);
});

test('a 3+ lead without msclkid is skipped for Bing with a reason', function () {
    Queue::fake();

    $lead = submitLeadForm(['msclkid' => '', 'gclid' => 'google-click-1'])->refresh();

    expect($lead->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($lead->bing_qualified_error)->toContain('No MSCLKID');
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
});

test('spam and leads older than the conversion window are not sent', function () {
    Queue::fake();
    $service = app(\App\Services\Ads\MicrosoftQualifiedLeadService::class);

    $spam = Lead::query()->create([
        'full_name' => 'Spam', 'email' => 's@example.com', 'phone' => '6505550000',
        'status' => Lead::STATUS_SPAM,
        'meta' => ['msclkid' => 'bing-spam', 'window_count' => '6-10'],
    ]);
    $old = Lead::query()->create([
        'full_name' => 'Old', 'email' => 'o@example.com', 'phone' => '6505550001',
        'meta' => ['msclkid' => 'bing-old', 'window_count' => '6-10'],
    ]);
    $old->created_at = now()->subDays(120);
    $old->save();

    $service->evaluate($spam);
    $service->evaluate($old);

    expect($spam->refresh()->bing_qualified_at)->toBeNull()
        ->and($old->refresh()->bing_qualified_status)->toBe(Lead::BING_QUALIFIED_SKIPPED)
        ->and($old->bing_qualified_error)->toContain('conversion window');
    Queue::assertNotPushed(SendLeadQualifiedToMicrosoftAds::class);
});
