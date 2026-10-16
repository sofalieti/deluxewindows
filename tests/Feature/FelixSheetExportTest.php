<?php

use App\Models\Lead;
use App\Services\Ads\FelixSheetExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.google_drive', [
        'auth' => 'oauth',
        'folder_id' => 'folder-test-id',
        'spreadsheet_id' => null,
        'appointments_spreadsheet_id' => 'appointments-sheet',
        'felix_spreadsheet_id' => null,
        'felix_spreadsheet_title' => 'Deluxe Windows Transfered to Felix',
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
});

test('felix export creates a workbook and writes every felix lead with a gclid column', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://drive.test/v3/files*' => function ($request) {
            if ($request->method() === 'GET') {
                return Http::response(['files' => []]);
            }

            return Http::response(['id' => 'felix-sheet']);
        },
        'https://sheets.test/v4/spreadsheets/felix-sheet?*' => Http::response([
            'sheets' => [
                ['properties' => ['sheetId' => 0, 'title' => 'Sheet1']],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/felix-sheet/values/A1' => Http::response([
            'values' => [],
        ], 200),
        'https://sheets.test/v4/spreadsheets/felix-sheet/values/*' => Http::response(['updates' => ['updatedRows' => 3]], 200),
    ]);

    $withClick = Lead::query()->create([
        'full_name' => 'Felix Client',
        'phone' => '6505550101',
        'email' => 'felix@example.com',
        'status' => Lead::STATUS_TRANSFERED_TO_FELIX,
        'meta' => ['gclid' => 'gclid-felix'],
    ]);
    $withoutClick = Lead::query()->create([
        'full_name' => 'No Click',
        'phone' => '6505550102',
        'email' => 'noclick@example.com',
        'status' => Lead::STATUS_TRANSFERED_TO_FELIX,
        'meta' => [],
    ]);
    Lead::query()->create([
        'full_name' => 'Still New',
        'phone' => '6505550103',
        'email' => 'new@example.com',
        'status' => Lead::STATUS_NEW,
        'meta' => ['gclid' => 'gclid-not-felix'],
    ]);

    $result = app(FelixSheetExporter::class)->exportPending();

    expect($result['count'])->toBe(2)
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/felix-sheet/edit');

    $withClick->refresh();
    $withoutClick->refresh();
    expect($withClick->felix_sheet_exported_at)->not->toBeNull()
        ->and($withoutClick->felix_sheet_exported_at)->not->toBeNull();

    Http::assertSent(function ($request) {
        if ($request->method() !== 'PUT' || ! str_contains($request->url(), '/spreadsheets/felix-sheet/values/')) {
            return false;
        }

        $values = $request['values'] ?? [];

        return ($values[0][7] ?? null) === 'GCLID'
            && ($values[1][0] ?? null) === 'Felix Client'
            && ($values[1][7] ?? null) === 'gclid-felix'
            && ($values[2][0] ?? null) === 'No Click'
            && ($values[2][7] ?? null) === '';
    });
});
