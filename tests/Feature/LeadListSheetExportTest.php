<?php

use App\Jobs\SyncLeadToListSheet;
use App\Models\Lead;
use App\Services\Ads\LeadListSheetExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
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
        'leads_spreadsheet_id' => 'leads-sheet',
        'leads_list_sync' => true,
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

test('leads land on a tab per source and a status change overwrites the same row', function () {
    Bus::fake();

    $tabs = [
        ['properties' => ['sheetId' => 0, 'title' => 'Sheet1']],
    ];
    $grids = [];

    Http::fake(function ($request) use (&$tabs, &$grids) {
        $url = $request->url();
        $data = $request->data();

        if (str_contains($url, 'oauth2.test/token')) {
            return Http::response(['access_token' => 'drive-access', 'expires_in' => 3600]);
        }

        if (str_contains($url, ':batchUpdate') && ! str_contains($url, '/values')) {
            $title = (string) data_get($data, 'requests.0.addSheet.properties.title');
            $tabs[] = ['properties' => ['sheetId' => count($tabs), 'title' => $title]];

            return Http::response(['replies' => [[]]]);
        }

        if (str_contains($url, '/values:batchUpdate')) {
            foreach ($data['data'] ?? [] as $item) {
                $range = (string) ($item['range'] ?? '');
                $title = sheet_title_from_range($range);
                if (preg_match('/!A(\d+):/', $range, $match) === 1) {
                    $grids[$title][(int) $match[1] - 1] = $item['values'][0];
                }
            }

            return Http::response(['totalUpdatedRows' => count($data['data'] ?? [])]);
        }

        if ($request->method() === 'PUT' && str_contains($url, '/values/')) {
            $range = (string) ($data['range'] ?? '');
            $title = sheet_title_from_range($range);
            $grids[$title] = $data['values'];

            return Http::response(['updatedRows' => count($data['values'] ?? [])]);
        }

        if ($request->method() === 'GET' && str_contains($url, '/values/')) {
            $title = sheet_title_from_range(rawurldecode($url));

            return Http::response(['values' => $grids[$title] ?? []]);
        }

        if ($request->method() === 'GET' && str_contains($url, '/spreadsheets/leads-sheet')) {
            return Http::response(['sheets' => $tabs]);
        }

        return Http::response('unexpected '.$request->method().' '.$url, 500);
    });

    $microsoft = Lead::query()->create([
        'full_name' => 'Mina Ads',
        'phone' => '(650) 555-1212',
        'email' => 'mina@example.com',
        'city' => 'San Mateo',
        'message' => 'Need new windows',
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'bay-area',
        'status' => Lead::STATUS_NEW,
        'meta' => [
            'utm_content' => 'ad-1',
            'utm_term' => 'windows',
            'utm_city' => '999001',
            'msclkid' => 'ms-123',
            'window_count' => '3-5',
            'opening_kind' => 'windows',
            'referrer' => 'https://www.bing.com/',
            'first_touch' => [
                'utm_source' => 'bing',
                'utm_medium' => 'cpc',
                'utm_city' => '888002',
                'msclkid' => 'ms-first',
            ],
        ],
    ]);
    Lead::query()->create([
        'full_name' => 'Greta Ads',
        'phone' => '6505550002',
        'email' => 'greta@example.com',
        'message' => 'Patio door',
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'status' => Lead::STATUS_NEW,
        'meta' => [
            'gclid' => 'gclid-greta',
            'window_count' => '2',
            'opening_kind' => 'doors',
        ],
    ]);

    $exporter = app(LeadListSheetExporter::class);
    $result = $exporter->exportCreatedWithinDays(3);

    expect($result['count'])->toBe(2)
        ->and($result['spreadsheet_url'])->toBe('https://docs.google.com/spreadsheets/d/leads-sheet/edit')
        ->and(collect($tabs)->pluck('properties.title')->all())->toContain('Microsoft', 'Google');

    $microsoftRow = implode("\t", $grids['Microsoft'][1]);
    $windows = Lead::OPENING_KINDS['windows'].' · '.Lead::WINDOW_COUNTS['3-5'];

    expect($grids['Microsoft'][0][0])->toBe('Lead ID')
        ->and($grids['Microsoft'])->toHaveCount(2)
        ->and($microsoftRow)->toContain('Mina Ads')
        ->and($microsoftRow)->toContain('Need new windows')
        ->and($microsoftRow)->toContain($windows)
        ->and($microsoftRow)->toContain('New')
        ->and($microsoftRow)->toContain('ms-123')
        ->and($microsoftRow)->toContain('ms-first')
        ->and($microsoftRow)->toContain('999001 (unmatched)')
        ->and($microsoftRow)->toContain('888002 (unmatched)')
        ->and($microsoftRow)->toContain('bay-area');

    expect(implode("\t", $grids['Google'][1]))->toContain('gclid-greta')
        ->and($grids['Google'])->toHaveCount(2);

    $microsoft->status = Lead::STATUS_QUOTED;
    $microsoft->save();
    $exporter->syncLead($microsoft->fresh());

    expect($grids['Microsoft'])->toHaveCount(2)
        ->and(implode("\t", $grids['Microsoft'][1]))->toContain('Quoted')
        ->and(implode("\t", $grids['Microsoft'][1]))->toContain((string) $microsoft->id);

    $again = $exporter->syncUpdatedWithinMinutes(60);
    expect($again['count'])->toBe(0)
        ->and($grids['Microsoft'])->toHaveCount(2);

    $old = Lead::query()->create([
        'full_name' => 'Old Direct',
        'email' => 'old@example.com',
        'phone' => '6505550009',
        'message' => 'From last month',
    ]);
    $old->forceFill([
        'created_at' => now()->subDays(20),
        'updated_at' => now()->subDays(20),
    ])->saveQuietly();

    $all = $exporter->exportAll();

    expect($all['count'])->toBe(3)
        ->and($grids['Microsoft'])->toHaveCount(2)
        ->and(implode("\t", $grids['Direct'][1]))->toContain('Old Direct')
        ->and(implode("\t", $grids['Direct'][1]))->toContain('From last month');
});

test('creating a lead and changing its status queue a sheet sync', function () {
    Bus::fake();

    $lead = Lead::query()->create([
        'full_name' => 'Queue Person',
        'email' => 'queue@example.com',
        'phone' => '6505550099',
        'status' => Lead::STATUS_NEW,
    ]);

    Bus::assertDispatched(SyncLeadToListSheet::class, fn (SyncLeadToListSheet $job): bool => $job->leadId === $lead->id);

    $lead->message = 'Just a note';
    $lead->save();

    Bus::assertDispatchedTimes(SyncLeadToListSheet::class, 1);

    $lead->status = Lead::STATUS_CONTACTED;
    $lead->save();

    Bus::assertDispatchedTimes(SyncLeadToListSheet::class, 2);
});

function sheet_title_from_range(string $range): string
{
    if (preg_match("/'((?:''|[^'])*)'!/", $range, $match) !== 1) {
        return '';
    }

    return str_replace("''", "'", $match[1]);
}
