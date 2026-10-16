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

test('appointment export appends sheet rows that are missing even if they were sent before', function () {
    $sheet = [['DE']];
    Http::fake(function ($request) use (&$sheet) {
        $url = $request->url();
        if (str_contains($url, 'oauth2.test/token')) {
            return Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]);
        }
        if (str_contains($url, '/spreadsheets/appointments-sheet?')) {
            return Http::response([
                'sheets' => [
                    ['properties' => ['sheetId' => 0, 'title' => 'Sheet1']],
                ],
            ]);
        }
        if ($request->method() === 'GET' && str_contains($url, '/values/')) {
            return Http::response(['values' => $sheet]);
        }
        if (in_array($request->method(), ['PUT', 'POST'], true) && str_contains($url, '/values/')) {
            $sheet = $request['values'];

            return Http::response(['updates' => ['updatedRows' => count($request['values'] ?? [])]]);
        }

        return Http::response('unexpected '.$request->method().' '.$url, 500);
    });

    $appointment = Lead::query()->create([
        'full_name' => 'Ada Client',
        'phone' => '(650) 555-1212',
        'email' => 'ada@example.com',
        'city' => 'San Mateo',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'status' => Lead::STATUS_APPOINTMENT,
        'appointments_sheet_exported_at' => now(),
        'meta' => ['gclid' => 'gclid-ada'],
    ]);
    Lead::query()->create([
        'full_name' => 'No Click Id',
        'phone' => '6505550002',
        'email' => 'noclick@example.com',
        'status' => Lead::STATUS_APPOINTMENT,
    ]);
    $bing = Lead::query()->create([
        'full_name' => 'Bing Client',
        'phone' => '6505550003',
        'email' => 'bing@example.com',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'status' => Lead::STATUS_APPOINTMENT,
        'meta' => [
            'gclid' => 'stale-google-gclid',
            'msclkid' => 'msclkid-bing',
        ],
    ]);
    Lead::query()->create([
        'full_name' => 'Not Yet',
        'phone' => '6505550004',
        'email' => 'notyet@example.com',
        'status' => Lead::STATUS_NEW,
        'meta' => ['gclid' => 'gclid-not-appointment'],
    ]);
    Lead::query()->create([
        'full_name' => 'Already Sent',
        'phone' => '6505550005',
        'email' => 'sent@example.com',
        'status' => Lead::STATUS_APPOINTMENT,
        'appointments_sheet_exported_at' => now(),
    ]);

    $result = app(AppointmentSheetExporter::class)->exportPending();

    expect($result['count'])->toBe(1)
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/appointments-sheet/edit');

    $appointment->refresh();
    expect($appointment->appointments_sheet_exported_at)->not->toBeNull();

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), '/spreadsheets/appointments-sheet/values/')
            && (($request['values'][0][7] ?? null) === 'GCLID')
            && (($request['values'][1][0] ?? null) === 'Ada Client')
            && in_array('gclid-ada', $request['values'][1], true);
    });

    $bing->refresh();
    expect($bing->appointments_sheet_exported_at)->toBeNull();

    $again = app(AppointmentSheetExporter::class)->exportPending();
    expect($again['count'])->toBe(0);
});
