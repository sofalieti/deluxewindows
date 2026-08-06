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

    $exporter = app(GoogleAdsOfflineSheetExporter::class);
    $resolved = $exporter->eligibleClicks('2026-08-05')
        ->get()
        ->map(fn (PhoneClick $click) => $click->resolvedGclid())
        ->all();

    expect($resolved)->toEqualCanonicalizing(['gclid-yesterday', 'first-only']);
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
        ->and($result['spreadsheet_id'])->toBeNull()
        ->and($result['title'])->toBe('Google Ads Offline Conversions 2026-08-05')
        ->and($result['click_ids'])->toBe([$click->id]);

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->toBeNull();
    Http::assertNothingSent();
});

test('export creates a Drive spreadsheet, writes values, and marks clicks', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://drive.test/v3/files' => Http::response(['id' => 'sheet-abc'], 200),
        'https://sheets.test/v4/spreadsheets/*' => Http::response(['updatedCells' => 10], 200),
    ]);

    $click = sheetClick(['gclid' => 'export-me']);

    $result = app(GoogleAdsOfflineSheetExporter::class)->export(null, false, false);

    expect($result['count'])->toBe(1)
        ->and($result['spreadsheet_id'])->toBe('sheet-abc')
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/sheet-abc/edit')
        ->and($result['dry_run'])->toBeFalse();

    $click->refresh();
    expect($click->google_ads_sheet_exported_at)->not->toBeNull()
        ->and($click->google_ads_sheet_url)->toBe('https://docs.google.com/spreadsheets/d/sheet-abc/edit');

    Http::assertSent(fn ($request) => $request->url() === 'https://drive.test/v3/files'
        && $request['name'] === 'Google Ads Offline Conversions 2026-08-05'
        && $request['mimeType'] === 'application/vnd.google-apps.spreadsheet'
        && $request['parents'] === ['folder-test-id']);

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/spreadsheets/sheet-abc/values/')) {
            return false;
        }

        $values = $request['values'] ?? [];

        return ($values[0][0] ?? null) === 'Parameters:TimeZone=America/Los_Angeles'
            && ($values[2][0] ?? null) === 'export-me';
    });
});

test('all-pending exports across days and skips already exported rows', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://drive.test/v3/files' => Http::response(['id' => 'sheet-pending'], 200),
        'https://sheets.test/v4/spreadsheets/*' => Http::response(['updatedCells' => 10], 200),
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
