<?php

use App\Models\PhoneClick;
use App\Services\Ads\GoogleAdsOfflineSheetExporter;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.timezone', 'America/Los_Angeles');
    config()->set('services.google_ads.phone_conversion_name', 'Phone Call Confirmed');
    config()->set('services.google_drive', [
        'auth' => 'oauth',
        'folder_id' => 'folder-test-id',
        'spreadsheet_id' => null,
        'service_account_json' => null,
        'client_id' => 'drive-client',
        'client_secret' => 'drive-secret',
        'refresh_token' => 'drive-refresh',
        'oauth_token_url' => 'https://oauth2.test/token',
        'timezone' => 'America/Los_Angeles',
        'drive_api_base_url' => 'https://drive.test/v3',
        'sheets_api_base_url' => 'https://sheets.test/v4',
    ]);

    Cache::flush();

    $this->travelTo(
        CarbonImmutable::parse('2026-08-06 12:00:00', 'America/Los_Angeles')
    );
});

afterEach(function () {
    $this->travelBack();
    CarbonImmutable::setTestNow();
});

function sheetClick(array $overrides = []): PhoneClick
{
    return PhoneClick::query()->create(array_merge([
        'phone' => '+16504614446',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_FOUND,
        'ringcentral_direction' => 'Inbound',
        'is_spam' => false,
        'gclid' => 'gclid-yesterday',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 13:54:40', 'America/Los_Angeles'),
    ], $overrides));
}

test('eligible filter keeps only RC-found non-spam clicks with a gclid', function () {
    sheetClick();
    sheetClick([
        'gclid' => null,
        'first_gclid' => 'first-only',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 10:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => 'spam-gclid',
        'is_spam' => true,
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 11:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => 'pending-gclid',
        'ringcentral_status' => PhoneClick::RINGCENTRAL_PENDING,
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 12:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => 'today-gclid',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-06 09:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => 'already-exported',
        'google_ads_sheet_exported_at' => now(),
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 08:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => null,
        'first_gclid' => 'stale-google-first',
        'msclkid' => 'bing-last-touch',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 14:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => 'should-not-export-bing',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'msclkid' => 'msclkid-paid',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 14:30:00', 'America/Los_Angeles'),
    ]);

    $exporter = app(GoogleAdsOfflineSheetExporter::class);
    $resolved = $exporter->eligibleClicks('2026-08-05')
        ->get()
        ->map(fn (PhoneClick $click) => $click->resolvedGclid())
        ->all();

    expect($resolved)->toEqualCanonicalizing(['gclid-yesterday', 'first-only']);
});

test('eligible filter excludes Microsoft Ads / Bing last-touch conversions', function () {
    sheetClick([
        'gclid' => 'google-ok',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 09:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => null,
        'first_gclid' => 'old-gclid',
        'msclkid' => 'ms-click',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 10:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick([
        'gclid' => null,
        'first_gclid' => 'utm-bing-only',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 11:00:00', 'America/Los_Angeles'),
    ]);

    $exporter = app(GoogleAdsOfflineSheetExporter::class);
    $resolved = $exporter->eligibleClicks('2026-08-05')
        ->get()
        ->map(fn (PhoneClick $click) => $click->resolvedGclid())
        ->all();

    expect($resolved)->toBe(['google-ok']);
});

test('template grid matches the official Google Ads GCLID import format', function () {
    $click = sheetClick([
        'gclid' => 'EaIQidCqis0CFeDnQgUdZo8DHg',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-05 13:54:40', 'America/Los_Angeles'),
    ]);

    $exporter = app(GoogleAdsOfflineSheetExporter::class);
    $rows = $exporter->buildDataRows(collect([$click]));
    $grid = $exporter->buildGrid($rows);

    expect($grid[0])->toBe(['Parameters:TimeZone=America/Los_Angeles'])
        ->and($grid[1])->toBe([
            'Google Click ID',
            'Conversion Name',
            'Conversion Time',
            'Conversion Value',
            'Conversion Currency',
            'Ad User Data',
            'Ad Personalization',
        ])
        ->and($grid[2][0])->toBe('EaIQidCqis0CFeDnQgUdZo8DHg')
        ->and($grid[2][1])->toBe('Phone Call Confirmed')
        ->and($grid[2][2])->toBe('2026-08-05 13:54:40-0700')
        ->and($grid[2][3])->toBe('1')
        ->and($grid[2][4])->toBe('USD')
        ->and($grid[2][5])->toBe('')
        ->and($grid[2][6])->toBe('');
});

test('dry-run builds yesterday rows without calling Drive or marking clicks', function () {
    Http::fake();
    $click = sheetClick();

    $result = app(GoogleAdsOfflineSheetExporter::class)->export(null, false, true);

    expect($result['dry_run'])->toBeTrue()
        ->and($result['count'])->toBe(1)
        ->and($result['title'])->toContain('Google Ads Offline Conversions')
        ->and($result['click_ids'])->toBe([$click->id]);

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->toBeNull();
    Http::assertNothingSent();
});

test('export creates one shared spreadsheet then appends rows and marks clicks', function () {
    Http::fake(function (\Illuminate\Http\Client\Request $request) {
        $url = $request->url();

        if ($url === 'https://oauth2.test/token') {
            return Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]);
        }

        // First resolve: list finds nothing → create.
        if ($request->method() === 'GET' && str_starts_with($url, 'https://drive.test/v3/files')) {
            return Http::response(['files' => []], 200);
        }

        if ($request->method() === 'POST' && $url === 'https://drive.test/v3/files') {
            return Http::response(['id' => 'sheet-abc'], 200);
        }

        // After create: seed header via PUT; later GET header check; then append.
        if (str_contains($url, '/spreadsheets/sheet-abc/values/A1:B2') && $request->method() === 'GET') {
            return Http::response([
                'values' => [
                    ['Parameters:TimeZone=America/Los_Angeles'],
                    ['Google Click ID', 'Conversion Name'],
                ],
            ], 200);
        }

        if (str_contains($url, '/spreadsheets/sheet-abc/values/') && in_array($request->method(), ['PUT', 'POST'], true)) {
            return Http::response(['updatedCells' => 10], 200);
        }

        return Http::response(['error' => 'unexpected '.$request->method().' '.$url], 500);
    });

    $click = sheetClick(['gclid' => 'export-me']);

    $result = app(GoogleAdsOfflineSheetExporter::class)->export(null, false, false);

    expect($result['count'])->toBe(1)
        ->and($result['spreadsheet_id'])->toBe('sheet-abc')
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/sheet-abc/edit')
        ->and($result['title'])->toBe('Google Ads Offline Conversions')
        ->and($result['dry_run'])->toBeFalse();

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->not->toBeNull()
        ->and($click->google_ads_sheet_url)->toBe('https://docs.google.com/spreadsheets/d/sheet-abc/edit');

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'https://drive.test/v3/files'
        && $request['name'] === 'Google Ads Offline Conversions'
        && $request['parents'] === ['folder-test-id']);

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/spreadsheets/sheet-abc/values/')
            && str_contains($request->url(), 'append')
            && (($request['values'][0][0] ?? null) === 'export-me');
    });
});

test('export appends into a configured spreadsheet id without creating a new file', function () {
    config()->set('services.google_drive.spreadsheet_id', 'pinned-sheet');

    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://sheets.test/v4/spreadsheets/pinned-sheet/values/A1:B2' => Http::response([
            'values' => [
                ['Parameters:TimeZone=America/Los_Angeles'],
                ['Google Click ID'],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/pinned-sheet/values/*' => Http::response(['updates' => ['updatedRows' => 1]], 200),
    ]);

    $click = sheetClick(['gclid' => 'pinned-row']);

    $result = app(GoogleAdsOfflineSheetExporter::class)->export(null, false, false);

    expect($result['spreadsheet_id'])->toBe('pinned-sheet')
        ->and($result['count'])->toBe(1);

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->not->toBeNull();

    Http::assertNotSent(fn ($request) => $request->url() === 'https://drive.test/v3/files'
        && $request->method() === 'POST');
});

test('all-pending exports across days and skips already exported rows', function () {
    config()->set('services.google_drive.spreadsheet_id', 'sheet-pending');

    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://sheets.test/v4/spreadsheets/sheet-pending/values/A1:B2' => Http::response([
            'values' => [
                ['Parameters:TimeZone=America/Los_Angeles'],
                ['Google Click ID'],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/sheet-pending/values/*' => Http::response(['updates' => ['updatedRows' => 2]], 200),
    ]);

    sheetClick([
        'gclid' => 'day-before',
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-04 15:00:00', 'America/Los_Angeles'),
    ]);
    sheetClick(['gclid' => 'yesterday']);
    sheetClick([
        'gclid' => 'done',
        'google_ads_sheet_exported_at' => now()->subDay(),
        'ringcentral_call_started_at' => CarbonImmutable::parse('2026-08-03 12:00:00', 'America/Los_Angeles'),
    ]);

    $result = app(GoogleAdsOfflineSheetExporter::class)->export(null, true, false);

    expect($result['count'])->toBe(2)
        ->and($result['rows'])->toHaveCount(2);
});

test('ads:export-google-offline-sheet dry-run succeeds via artisan', function () {
    sheetClick();

    $this->artisan('ads:export-google-offline-sheet', ['--dry-run' => true])
        ->expectsOutputToContain('Dry run: 1 row(s)')
        ->assertSuccessful();
});

test('exportClick appends a single confirmed Google click into the pinned sheet', function () {
    config()->set('services.google_drive.spreadsheet_id', 'live-sheet');

    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://sheets.test/v4/spreadsheets/live-sheet/values/A1:B2' => Http::response([
            'values' => [
                ['Parameters:TimeZone=America/Los_Angeles'],
                ['Google Click ID'],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/live-sheet/values/*' => Http::response(['updates' => ['updatedRows' => 1]], 200),
    ]);

    $click = sheetClick(['gclid' => 'live-gclid']);

    $ok = app(GoogleAdsOfflineSheetExporter::class)->exportClick($click);

    expect($ok)->toBeTrue();
    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->not->toBeNull()
        ->and($click->google_ads_sheet_url)->toBe('https://docs.google.com/spreadsheets/d/live-sheet/edit');

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && str_contains($request->url(), 'append')
        && (($request['values'][0][0] ?? null) === 'live-gclid'));
});

test('SendPhoneClickOfflineConversions appends to the Drive sheet after confirm', function () {
    config()->set('services.google_drive.spreadsheet_id', 'job-sheet');
    config()->set('services.google_drive.auth', 'oauth');
    config()->set('services.google_drive.client_id', 'drive-client');
    config()->set('services.google_drive.client_secret', 'drive-secret');
    config()->set('services.google_drive.refresh_token', 'drive-refresh');
    config()->set('services.google_drive.oauth_token_url', 'https://oauth2.test/token');
    config()->set('services.google_drive.sheets_api_base_url', 'https://sheets.test/v4');
    config()->set('services.google_ads.developer_token', ''); // skip API upload
    config()->set('services.microsoft_ads.developer_token', '');

    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://sheets.test/v4/spreadsheets/job-sheet/values/A1:B2' => Http::response([
            'values' => [
                ['Parameters:TimeZone=America/Los_Angeles'],
                ['Google Click ID'],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/job-sheet/values/*' => Http::response(['updates' => ['updatedRows' => 1]], 200),
    ]);

    $click = sheetClick(['gclid' => 'from-job']);

    (new \App\Jobs\SendPhoneClickOfflineConversions($click->id))->handle(
        app(\App\Services\Ads\GoogleAdsOfflineConversionService::class),
        app(\App\Services\Ads\MicrosoftAdsOfflineConversionService::class),
        app(GoogleAdsOfflineSheetExporter::class),
    );

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->not->toBeNull();
});
