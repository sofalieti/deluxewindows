<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Lead;
use App\Services\ServiceAreaRegions;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Upserts every website lead into one workbook, with a tab per traffic source.
 * Existing rows are matched by Lead ID and overwritten, so a status change
 * updates the same line instead of appending a second one.
 */
final class LeadListSheetExporter
{
    public const HEADER = [
        'Lead ID',
        'Created',
        'Name',
        'Phone',
        'Email',
        'City',
        'Determined city',
        'First determined city',
        'Description',
        'Windows / doors',
        'Status',
        'Source',
        'Page',
        'Referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'first_utm_source',
        'first_utm_medium',
        'first_utm_campaign',
        'first_utm_content',
        'first_utm_term',
        'gclid',
        'gbraid',
        'wbraid',
        'first_gclid',
        'first_gbraid',
        'first_wbraid',
        'msclkid',
        'first_msclkid',
        'fbclid',
        'utm_city',
    ];

    public function __construct(
        private readonly GoogleAdsOfflineSheetExporter $sheets,
        private readonly ServiceAreaRegions $regions,
    ) {}

    public function spreadsheetId(): string
    {
        return trim((string) config('services.google_drive.leads_spreadsheet_id'));
    }

    public function spreadsheetUrl(): string
    {
        $id = $this->spreadsheetId();

        return $id === '' ? '' : 'https://docs.google.com/spreadsheets/d/'.$id.'/edit';
    }

    public function configurationError(): ?string
    {
        if ($this->spreadsheetId() === '') {
            return 'Set GOOGLE_DRIVE_LEADS_SPREADSHEET_ID in .env.';
        }

        return $this->sheets->configurationError();
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function exportAll(): array
    {
        $this->assertReady();

        $count = 0;
        Lead::query()
            ->orderBy('id')
            ->chunkById(200, function ($leads) use (&$count): void {
                $count += $this->syncLeads($leads);
            });

        return [
            'count' => $count,
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function exportCreatedWithinDays(int $days): array
    {
        $this->assertReady();

        $leads = Lead::query()
            ->where('created_at', '>=', now()->subDays(max(1, $days)))
            ->orderBy('id')
            ->get();

        return [
            'count' => $this->syncLeads($leads),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function syncUpdatedWithinMinutes(int $minutes): array
    {
        $this->assertReady();

        $leads = Lead::query()
            ->where('updated_at', '>=', now()->subMinutes(max(1, $minutes)))
            ->orderBy('id')
            ->get();

        return [
            'count' => $this->syncLeads($leads, onlyChanges: true),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    public function syncLead(Lead $lead): void
    {
        $this->assertReady();
        $this->syncLeads([$lead]);
    }

    public function sheetTitleFor(Lead $lead): string
    {
        return match ((string) $lead->traffic_source) {
            'microsoft_ads' => 'Microsoft',
            'google_ads' => 'Google',
            'direct' => 'Direct',
            'seo_google', 'seo_bing', 'seo_other' => 'SEO',
            'chatgpt' => 'ChatGPT',
            default => 'Other',
        };
    }

    /**
     * @param  iterable<Lead>  $leads
     */
    public function syncLeads(iterable $leads, bool $onlyChanges = false): int
    {
        /** @var array<string, list<Lead>> $groups */
        $groups = [];
        foreach ($leads as $lead) {
            $groups[$this->sheetTitleFor($lead)][] = $lead;
        }

        $count = 0;
        foreach ($groups as $title => $group) {
            $count += $this->syncGroup($title, $group, $onlyChanges);
        }

        return $count;
    }

    /**
     * @param  list<Lead>  $leads
     */
    private function syncGroup(string $title, array $leads, bool $onlyChanges): int
    {
        if ($leads === []) {
            return 0;
        }

        $spreadsheetId = $this->spreadsheetId();
        $this->sheets->ensureSheetTab($spreadsheetId, $title);

        $existing = $this->sheets->readTabRows($spreadsheetId, $title, count(self::HEADER));
        $idToRow = $this->leadIdRows($existing);
        $headerReady = ($existing[0][0] ?? '') === 'Lead ID';
        $statusIndex = (int) array_search('Status', self::HEADER, true);

        $updates = [];
        $appends = [];
        $written = 0;
        foreach ($leads as $lead) {
            $row = $this->row($lead);
            $rowNumber = $idToRow[(string) $lead->id] ?? null;
            if ($rowNumber !== null) {
                $currentStatus = trim((string) ($existing[$rowNumber - 1][$statusIndex] ?? ''));
                if ($onlyChanges && $currentStatus === $row[$statusIndex]) {
                    continue;
                }
                $updates[] = [
                    'range' => $this->rowRange($title, $rowNumber, count($row)),
                    'values' => [$row],
                ];
                $written++;
                continue;
            }

            $appends[] = $row;
            $written++;
            $idToRow[(string) $lead->id] = -1;
        }

        if (! $headerReady && $existing === []) {
            if ($appends !== []) {
                $this->sheets->writeTabGrid($spreadsheetId, $title, array_merge([self::HEADER], $appends));
            }

            return $written;
        }

        $this->sheets->batchUpdateTabRows($spreadsheetId, $updates);
        $this->sheets->appendTabRows($spreadsheetId, $title, $appends);

        return $written;
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array<string, int>
     */
    private function leadIdRows(array $rows): array
    {
        $map = [];
        foreach ($rows as $index => $row) {
            $id = trim((string) ($row[0] ?? ''));
            if ($id !== '' && ctype_digit($id)) {
                $map[$id] = $index + 1;
            }
        }

        return $map;
    }

    /**
     * @return list<string>
     */
    private function row(Lead $lead): array
    {
        $first = data_get($lead->meta, 'first_touch');
        $first = is_array($first) ? $first : [];
        $touch = static function (string $key) use ($first, $lead): string {
            $fromFirst = trim((string) ($first[$key] ?? ''));
            if ($fromFirst !== '') {
                return $fromFirst;
            }

            return $lead->metaValue('first_'.$key);
        };

        $lastPlatform = $this->regions->platformFromAttribution([
            'utm_source' => $lead->utm_source,
            'gclid' => $lead->metaValue('gclid'),
            'gbraid' => $lead->metaValue('gbraid'),
            'wbraid' => $lead->metaValue('wbraid'),
            'msclkid' => $lead->metaValue('msclkid'),
        ]);
        $firstPlatform = $this->regions->platformFromAttribution([
            'utm_source' => $touch('utm_source'),
            'gclid' => $touch('gclid'),
            'gbraid' => $touch('gbraid'),
            'wbraid' => $touch('wbraid'),
            'msclkid' => $touch('msclkid'),
        ]);

        $timezone = trim((string) config('services.google_drive.timezone', 'America/Los_Angeles'));
        if ($timezone === '') {
            $timezone = 'America/Los_Angeles';
        }

        $created = $lead->created_at !== null
            ? CarbonImmutable::parse($lead->created_at)->setTimezone($timezone)->format('Y-m-d H:i')
            : '';

        return $this->pad([
            (string) $lead->id,
            $created,
            trim((string) $lead->full_name),
            trim((string) $lead->phone),
            trim((string) $lead->email),
            trim((string) $lead->city),
            $this->cityLabel($lead->metaValue('utm_city'), $lastPlatform),
            $this->cityLabel($touch('utm_city'), $firstPlatform),
            trim((string) $lead->message),
            $lead->openingSummary(),
            Lead::STATUSES[$lead->status] ?? (string) $lead->status,
            $lead->trafficSourceLabel(),
            trim((string) $lead->page_url),
            $lead->metaValue('referrer'),
            trim((string) $lead->utm_source),
            trim((string) $lead->utm_medium),
            trim((string) $lead->utm_campaign),
            $lead->metaValue('utm_content'),
            $lead->metaValue('utm_term'),
            $touch('utm_source'),
            $touch('utm_medium'),
            $touch('utm_campaign'),
            $touch('utm_content'),
            $touch('utm_term'),
            $lead->metaValue('gclid'),
            $lead->metaValue('gbraid'),
            $lead->metaValue('wbraid'),
            $touch('gclid'),
            $touch('gbraid'),
            $touch('wbraid'),
            $lead->metaValue('msclkid'),
            $touch('msclkid'),
            $lead->metaValue('fbclid'),
            $lead->metaValue('utm_city'),
        ]);
    }

    private function cityLabel(string $raw, ?string $platform): string
    {
        if (trim($raw) === '') {
            return '';
        }

        $label = $this->regions->utmCityLabel($raw, $platform);

        return $label === '-' ? '' : $label;
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function pad(array $row): array
    {
        $width = count(self::HEADER);
        if (count($row) > $width) {
            return array_slice($row, 0, $width);
        }

        return array_pad($row, $width, '');
    }

    private function rowRange(string $title, int $rowNumber, int $columns): string
    {
        $letter = '';
        $index = max(1, $columns);
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26);
        }

        $quoted = "'".str_replace("'", "''", $title)."'";

        return $quoted.'!A'.$rowNumber.':'.$letter.$rowNumber;
    }

    private function assertReady(): void
    {
        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }
    }
}
