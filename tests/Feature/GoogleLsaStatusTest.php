<?php

use App\Services\Ads\GoogleLsaClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.google_lsa', [
        'client_id' => 'lsa-client',
        'client_secret' => 'lsa-secret',
        'refresh_token' => 'lsa-refresh',
        'manager_customer_id' => '123-456-7890',
        'oauth_redirect_uri' => 'http://localhost',
        'oauth_token_url' => 'https://oauth2.test/token',
        'api_base_url' => 'https://localservices.test',
    ]);

    config()->set('services.google_ads.login_customer_id', '');
    config()->set('services.google_ads.customer_id', '');
});

test('the lsa status command reports accounts and lead types', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'access', 'expires_in' => 3600]),
        'https://localservices.test/v1/accountReports:search*' => Http::response([
            'accountReports' => [[
                'accountId' => '999',
                'businessName' => 'Deluxe Windows',
                'currentPeriodChargedLeads' => '3',
                'currentPeriodPhoneCalls' => '8',
                'currentPeriodTotalCost' => '120.5',
            ]],
        ]),
        'https://localservices.test/v1/detailedLeadReports:search*' => Http::response([
            'detailedLeadReports' => [[
                'leadType' => 'PHONE_CALL',
                'chargeStatus' => 'CHARGED',
                'leadCreationTimestamp' => '2026-11-04T18:00:00Z',
                'phoneLead' => ['consumerPhoneNumber' => '+14155550100'],
            ]],
        ]),
    ]);

    \Illuminate\Support\Facades\Artisan::call('ads:lsa-status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('charged leads 3')
        ->and($output)->toContain('Deluxe Windows')
        ->and($output)->toContain('PHONE_CALL')
        ->and($output)->toContain('1234567890')
        ->and($output)->not->toContain('+14155550100');
});

test('the lsa status command fails before any api call when the refresh token is missing', function () {
    config()->set('services.google_lsa.refresh_token', '');
    Http::fake();

    $this->artisan('ads:lsa-status')
        ->expectsOutputToContain('ads:lsa-oauth')
        ->assertFailed();

    Http::assertNothingSent();
});

test('the lsa oauth command prints a consent url and can exchange a code', function () {
    expect(app(GoogleLsaClient::class)->authorizeUrl())
        ->toContain('client_id=lsa-client')
        ->toContain('scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fadwords')
        ->toContain('access_type=offline')
        ->toContain('prompt=consent');

    $this->artisan('ads:lsa-oauth')
        ->expectsOutputToContain('https://accounts.google.com/o/oauth2/v2/auth?')
        ->assertSuccessful();

    Http::fake([
        'https://oauth2.test/token' => Http::response(['refresh_token' => 'refresh-new']),
    ]);

    $this->artisan('ads:lsa-oauth', ['--code' => 'auth-code'])
        ->expectsOutputToContain('GOOGLE_LSA_REFRESH_TOKEN=refresh-new')
        ->assertSuccessful();
});

test('the lsa env writer updates one key and leaves the rest of the file', function () {
    $path = tempnam(sys_get_temp_dir(), 'lsa-env');
    file_put_contents($path, "APP_NAME=Deluxe\nGOOGLE_LSA_CLIENT_ID=old\n");

    GoogleLsaClient::upsertEnvFile($path, [
        'GOOGLE_LSA_CLIENT_ID' => 'new-id',
        'GOOGLE_LSA_CLIENT_SECRET' => 'new-secret',
        'GOOGLE_LSA_REFRESH_TOKEN' => '',
    ]);

    $written = file_get_contents($path);
    expect($written)
        ->toContain('APP_NAME=Deluxe')
        ->toContain('GOOGLE_LSA_CLIENT_ID=new-id')
        ->toContain('GOOGLE_LSA_CLIENT_SECRET=new-secret')
        ->not->toContain('GOOGLE_LSA_REFRESH_TOKEN=');

    @unlink($path);
});
