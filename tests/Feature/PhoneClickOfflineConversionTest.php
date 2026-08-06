<?php

use App\Jobs\SendPhoneClickOfflineConversions;
use App\Models\PhoneClick;
use App\Services\Ads\GoogleAdsOfflineConversionService;
use App\Services\Ads\MicrosoftAdsOfflineConversionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.google_ads', [
        'developer_token' => 'dev-token',
        'client_id' => 'google-client',
        'client_secret' => 'google-secret',
        'refresh_token' => 'google-refresh',
        'customer_id' => '123-456-7890',
        'login_customer_id' => '999-888-7777',
        'phone_conversion_action' => 'customers/1234567890/conversionActions/555',
        'oauth_redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
        'api_version' => 'v18',
        'api_base_url' => 'https://googleads.test',
        'oauth_token_url' => 'https://oauth2.test/token',
    ]);

    config()->set('services.microsoft_ads', [
        'developer_token' => 'ms-dev-token',
        'client_id' => 'ms-client',
        'client_secret' => 'ms-secret',
        'refresh_token' => 'ms-refresh',
        'customer_id' => '4242',
        'account_id' => '2424',
        'phone_conversion_name' => 'Phone Call Confirmed',
        'identity_provider' => 'microsoft',
        'oauth_redirect_uri' => 'https://login.test/native',
        'api_base_url' => 'https://campaign.bingads.test',
        'oauth_token_url' => 'https://login.test/token',
        'oauth_authorize_url' => 'https://login.test/authorize',
        'google_oauth_token_url' => 'https://google-oauth.test/token',
        'google_oauth_authorize_url' => 'https://google-oauth.test/authorize',
    ]);

    Cache::flush();

    // Pins the clock so the 90 day import window keeps behaving the same over time.
    // Must stay in the app timezone: Carbon reads DB datetimes in the frozen clock's zone.
    $this->travelTo(
        CarbonImmutable::parse('2026-08-04 12:00:00 UTC')->setTimezone((string) config('app.timezone'))
    );
});

afterEach(function () {
    $this->travelBack();
    CarbonImmutable::setTestNow();
});

function confirmedClick(array $overrides = []): PhoneClick
{
    return PhoneClick::query()->create(array_merge([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_direction' => 'Inbound',
        'ringcentral_from_phone' => '+14155550999',
        'ringcentral_to_phone' => '+16504614446',
        // Stored in the app timezone, exactly like MatchPhoneClickToRingCentral does.
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-04 10:15:00 UTC')
            ->setTimezone((string) config('app.timezone')),
    ], $overrides));
}

function fakeAdsEndpoints(): void
{
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'google-access', 'expires_in' => 3600]),
        'https://googleads.test/*' => Http::response(['results' => [['gclid' => 'abc']]]),
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response(['PartialErrors' => []]),
    ]);
}

test('a confirmed call with a gclid is uploaded to Google Ads', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['gclid' => 'test-gclid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->google_ads_conversion_sent_at)->not->toBeNull()
        ->and($click->google_ads_conversion_error)->toBeNull()
        ->and($click->bing_ads_conversion_sent_at)->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), ':uploadClickConversions')
        && $request['conversions'][0]['gclid'] === 'test-gclid'
        && $request['conversions'][0]['orderId'] === 'phone-click-'.$click->id
        && $request->header('developer-token')[0] === 'dev-token'
        && $request->header('login-customer-id')[0] === '9998887777');
});

test('a confirmed call with an msclkid is uploaded to Microsoft Ads', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->bing_ads_conversion_sent_at)->not->toBeNull()
        ->and($click->bing_ads_conversion_error)->toBeNull()
        ->and($click->google_ads_conversion_sent_at)->toBeNull();

    Http::assertSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply')
        && $request['OfflineConversions'][0]['MicrosoftClickId'] === 'test-msclkid'
        && $request['OfflineConversions'][0]['ConversionName'] === 'Phone Call Confirmed'
        && $request['OfflineConversions'][0]['ConversionTime'] === '2026-08-04T10:15:00Z');
});

test('the caller number is sent to Microsoft as a SHA-256 hash for enhanced conversions', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    Http::assertSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply')
        && $request['OfflineConversions'][0]['HashedPhoneNumber'] === hash('sha256', '+14155550999'));
});

test('a Google signed in Bing account authenticates through Google and flags the identity provider', function () {
    config()->set('services.microsoft_ads.identity_provider', 'google');

    Http::fake([
        'https://google-oauth.test/token' => Http::response(['access_token' => 'google-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response(['PartialErrors' => []]),
    ]);

    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->bing_ads_conversion_sent_at)->not->toBeNull();

    // Google refuses a refresh that asks for a scope it never granted.
    Http::assertSent(fn ($request) => $request->url() === 'https://google-oauth.test/token'
        && $request['grant_type'] === 'refresh_token'
        && ! isset($request['scope']));

    Http::assertSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply')
        && $request->header('IdentityProvider')[0] === 'Google');

    Http::assertNotSent(fn ($request) => $request->url() === 'https://login.test/token');
});

test('the Microsoft identity path does not announce a Google provider', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    Http::assertSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply')
        && $request->header('IdentityProvider') === []);
});

test('a call older than the Microsoft import window is not uploaded', function () {
    fakeAdsEndpoints();
    $click = confirmedClick([
        'msclkid' => 'test-msclkid',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-01-01 10:00:00 UTC')
            ->setTimezone((string) config('app.timezone')),
    ]);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->bing_ads_conversion_sent_at)->toBeNull()
        ->and($click->bing_ads_conversion_error)->toContain('90 day');

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply'));
});

test('first touch click ids are used when the last touch is empty', function () {
    fakeAdsEndpoints();
    $click = confirmedClick([
        'first_gclid' => 'first-touch-gclid',
        'first_msclkid' => 'first-touch-msclkid',
    ]);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    Http::assertSent(fn ($request) => str_contains($request->url(), ':uploadClickConversions')
        && $request['conversions'][0]['gclid'] === 'first-touch-gclid');
    Http::assertSent(fn ($request) => str_contains($request->url(), '/OfflineConversions/Apply')
        && $request['OfflineConversions'][0]['MicrosoftClickId'] === 'first-touch-msclkid');
});

test('an already uploaded conversion is not sent twice', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['gclid' => 'test-gclid']);

    $job = new SendPhoneClickOfflineConversions($click->id);
    $job->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );
    $sentAt = $click->refresh()->google_ads_conversion_sent_at;

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->google_ads_conversion_sent_at->timestamp)->toBe($sentAt->timestamp);

    Http::assertSentCount(2);
});

test('nothing is uploaded when the ad credentials are missing', function () {
    config()->set('services.google_ads.developer_token', '');
    config()->set('services.microsoft_ads.developer_token', '');
    Http::fake();

    $click = confirmedClick(['gclid' => 'test-gclid', 'msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->google_ads_conversion_sent_at)->toBeNull()
        ->and($click->bing_ads_conversion_sent_at)->toBeNull();

    Http::assertNothingSent();
});

test('a click without any ad click id is skipped', function () {
    Http::fake();
    $click = confirmedClick();

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->google_ads_conversion_sent_at)->toBeNull();
    Http::assertNothingSent();
});

test('an unconfirmed phone click is never uploaded', function () {
    Http::fake();
    $click = confirmedClick([
        'gclid' => 'test-gclid',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->google_ads_conversion_sent_at)->toBeNull();
    Http::assertNothingSent();
});

test('a spam phone click is never uploaded', function () {
    Http::fake();
    $click = confirmedClick(['gclid' => 'test-gclid', 'is_spam' => true]);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->google_ads_conversion_sent_at)->toBeNull();
    Http::assertNothingSent();
});

test('an upload failure is recorded without blocking the other platform', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'google-access', 'expires_in' => 3600]),
        'https://googleads.test/*' => Http::response(['error' => ['message' => 'Invalid gclid']], 400),
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response(['PartialErrors' => []]),
    ]);

    $click = confirmedClick(['gclid' => 'bad-gclid', 'msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->google_ads_conversion_sent_at)->toBeNull()
        ->and($click->google_ads_conversion_error)->toContain('Invalid gclid')
        ->and($click->bing_ads_conversion_sent_at)->not->toBeNull();
});

test('microsoft partial errors are treated as a failure', function () {
    Http::fake([
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response([
            'PartialErrors' => [['Message' => 'MSCLKID is invalid']],
        ]),
    ]);

    $click = confirmedClick(['msclkid' => 'expired-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($click->bing_ads_conversion_sent_at)->toBeNull()
        ->and($click->bing_ads_conversion_error)->toContain('MSCLKID is invalid');
});

test('a microsoft fault is reported with its code, message and tracking id', function () {
    Http::fake([
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response([
            'TrackingId' => 'trk-9182',
            'OperationErrors' => [[
                'Code' => 5615,
                'ErrorCode' => 'OfflineConversionNotAcceptedForGoal',
                'Message' => 'The conversion goal is not ready yet.',
                'Details' => 'Wait two hours after creating the goal.',
            ]],
        ], 500),
    ]);

    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $error = $click->refresh()->bing_ads_conversion_error;

    expect($error)->toContain('OfflineConversionNotAcceptedForGoal #5615')
        ->and($error)->toContain('The conversion goal is not ready yet.')
        ->and($error)->toContain('Wait two hours after creating the goal.')
        ->and($error)->toContain('TrackingId trk-9182')
        ->and($error)->not->toContain('unknown error');
});

test('an unrecognised microsoft fault falls back to the raw response body', function () {
    Http::fake([
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => Http::response('<html>Bad Gateway</html>', 502),
    ]);

    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    expect($click->refresh()->bing_ads_conversion_error)->toContain('Bad Gateway');
});

test('an expired microsoft token is refreshed and the upload retried once', function () {
    $attempts = 0;

    Http::fake([
        'https://login.test/token' => Http::response(['access_token' => 'ms-access', 'expires_in' => 3600]),
        'https://campaign.bingads.test/*' => function () use (&$attempts) {
            $attempts++;

            if ($attempts === 1) {
                return Http::response([
                    'TrackingId' => 'trk-1',
                    'Errors' => [[
                        'Code' => 105,
                        'ErrorCode' => 'AuthenticationTokenExpired',
                        'Message' => 'Authentication token expired.',
                    ]],
                ], 500);
            }

            return Http::response(['PartialErrors' => []]);
        },
    ]);

    $click = confirmedClick(['msclkid' => 'test-msclkid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    $click->refresh();

    expect($attempts)->toBe(2)
        ->and($click->bing_ads_conversion_sent_at)->not->toBeNull()
        ->and($click->bing_ads_conversion_error)->toBeNull();
});

test('the phone click screen shows the offline conversion status', function () {
    fakeAdsEndpoints();

    $user = \App\Models\User::factory()->create();
    $user->forceFill(['permissions' => ['platform.leads' => true]])->save();

    $click = confirmedClick(['gclid' => 'test-gclid']);

    $screen = $this->withoutMiddleware(\Orchid\Platform\Http\Middleware\Access::class)
        ->actingAs($user);

    $screen->get(route('platform.phone-clicks.view', $click))
        ->assertOk()
        ->assertSee('Google Ads conversion')
        ->assertSee('Microsoft Ads conversion')
        ->assertSee('No MSCLKID');

    $screen->post(route('platform.phone-clicks.view', [
        'click' => $click->id,
        'method' => 'resendOfflineConversions',
    ]), ['click' => $click->id])->assertRedirect();

    expect($click->refresh()->google_ads_conversion_sent_at)->not->toBeNull();

    $screen->get(route('platform.phone-clicks.view', $click))
        ->assertOk()
        ->assertSee('✓ Sent', escape: false);
});

test('the backfill command queues confirmed calls that were never uploaded', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $waiting = confirmedClick(['msclkid' => 'waiting-msclkid']);
    $alreadySent = confirmedClick([
        'msclkid' => 'sent-msclkid',
        'bing_ads_conversion_sent_at' => now(),
    ]);
    $withoutClickId = confirmedClick();
    $notConfirmed = confirmedClick([
        'msclkid' => 'pending-msclkid',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
    ]);

    $this->artisan('ads:backfill-offline-conversions', ['--days' => 7])->assertSuccessful();

    \Illuminate\Support\Facades\Queue::assertPushed(
        SendPhoneClickOfflineConversions::class,
        fn ($job) => $job->phoneClickId === $waiting->id,
    );

    foreach ([$alreadySent, $withoutClickId, $notConfirmed] as $skipped) {
        \Illuminate\Support\Facades\Queue::assertNotPushed(
            SendPhoneClickOfflineConversions::class,
            fn ($job) => $job->phoneClickId === $skipped->id,
        );
    }
});

test('the backfill command does nothing while the ad credentials are missing', function () {
    \Illuminate\Support\Facades\Queue::fake();
    config()->set('services.google_ads.developer_token', '');
    config()->set('services.microsoft_ads.developer_token', '');

    confirmedClick(['msclkid' => 'waiting-msclkid']);

    $this->artisan('ads:backfill-offline-conversions')->assertSuccessful();

    \Illuminate\Support\Facades\Queue::assertNothingPushed();
});

test('a forced resend uploads again after a successful send', function () {
    fakeAdsEndpoints();
    $click = confirmedClick(['gclid' => 'test-gclid']);

    (new SendPhoneClickOfflineConversions($click->id))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    (new SendPhoneClickOfflineConversions($click->id, force: true))->handle(
        app(GoogleAdsOfflineConversionService::class),
        app(MicrosoftAdsOfflineConversionService::class),
    );

    Http::assertSentCount(3);
});
