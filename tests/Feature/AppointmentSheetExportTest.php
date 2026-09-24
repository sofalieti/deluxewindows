<?php

use App\Models\Lead;
use App\Services\Ads\AppointmentSheetExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.google_ads.phone_conversion_name', 'Phone Call Confirmed');
    config()->set('services.google_drive', [
        'auth' => 'oauth',
        'folder_id' => 'folder-test-id',
        'spreadsheet_id' => null,
        'appointments_spreadsheet_id' => 'appointments-sheet',
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

test('appointment export appends only new appointment leads', function () {
    Http::fake([
        'https://oauth2.test/token' => Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]),
        'https://sheets.test/v4/spreadsheets/appointments-sheet?*' => Http::response([
            'sheets' => [
                ['properties' => ['sheetId' => 0, 'title' => 'Sheet1']],
            ],
        ], 200),
        'https://sheets.test/v4/spreadsheets/appointments-sheet/values/A1' => Http::response([
            'values' => [['DE']],
        ], 200),
        'https://sheets.test/v4/spreadsheets/appointments-sheet/values/*' => Http::response(['updates' => ['updatedRows' => 1]], 200),
    ]);

    $appointment = Lead::query()->create([
        'full_name' => 'Ada Client',
        'phone' => '(650) 555-1212',
        'email' => 'ada@example.com',
        'city' => 'San Mateo',
        'status' => Lead::STATUS_APPOINTMENT,
    ]);
    Lead::query()->create([
        'full_name' => 'Not Yet',
        'status' => Lead::STATUS_NEW,
    ]);
    Lead::query()->create([
        'full_name' => 'Already Sent',
        'status' => Lead::STATUS_APPOINTMENT,
        'appointments_sheet_exported_at' => now(),
    ]);

    $result = app(AppointmentSheetExporter::class)->exportPending();

    expect($result['count'])->toBe(1)
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/appointments-sheet/edit');

    $appointment->refresh();
    expect($appointment->appointments_sheet_exported_at)->not->toBeNull();

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/spreadsheets/appointments-sheet/values/')
            && str_contains($request->url(), 'append')
            && (($request['values'][0][0] ?? null) === 'Ada Client')
            && (($request['values'][0][1] ?? null) === '(650) 555-1212');
    });

    $again = app(AppointmentSheetExporter::class)->exportPending();
    expect($again['count'])->toBe(0);
});
