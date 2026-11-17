<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Lead;
use App\Models\PhoneClick;
use App\Services\ServiceAreaRegions;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Upserts website leads and RingCentral-confirmed phone clicks into one workbook,
 * with a tab per traffic source. Leads keep their numeric id. Calls use C{id}
 * so a call cannot overwrite a lead that happens to share the same number.
 * Existing rows are matched by that id and overwritten.
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

        /** @var array<string, list<array{id: string, at: int, row: list<string>}>> $groups */
        $groups = [];
        $leads = 0;
        $calls = 0;

        Lead::query()
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$groups, &$leads): void {
                foreach ($chunk as $lead) {
                    $leads++;
                    $groups[$this->sheetTitleFor($lead)][] = [
                        'id' => (string) $lead->id,
                        'at' => $lead->created_at?->getTimestamp() ?? 0,
                        'row' => $this->row($lead),
                    ];
                }
            });

        PhoneClick::query()
            ->confirmedByRingCentral()
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use (&$groups, &$calls): void {
                foreach ($chunk as $click) {
                    $calls++;
                    $groups[$this->sheetTitleForSource((string) $click->traffic_source)][] = [
                        'id' => 'C'.$click->id,
                        'at' => $click->created_at?->getTimestamp() ?? 0,
                        'row' => $this->clickRow($click),
                    ];
                }
            });

        foreach ($groups as $title => $items) {
            usort($items, static function (array $a, array $b): int {
                return $a['at'] <=> $b['at'] ?: strcmp($a['id'], $b['id']);
            });
            $this->rewriteTab($title, array_column($items, 'row'));
        }

        return [
            'count' => $leads + $calls,
            'leads' => $leads,
            'calls' => $calls,
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function exportCreatedWithinDays(int $days): array
    {
        $this->assertReady();

        $since = now()->subDays(max(1, $days));
        $leads = Lead::query()
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get();
        $clicks = PhoneClick::query()
            ->confirmedByRingCentral()
            ->where('created_at', '>=', $since)
            ->orderBy('id')
            ->get();

        return [
            'count' => $this->syncLeads($leads) + $this->syncClicks($clicks),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function syncUpdatedWithinMinutes(int $minutes): array
    {
        $this->assertReady();

        $since = now()->subMinutes(max(1, $minutes));
        $leads = Lead::query()
            ->where('updated_at', '>=', $since)
            ->orderBy('id')
            ->get();
        $clicks = PhoneClick::query()
            ->confirmedByRingCentral()
            ->where('updated_at', '>=', $since)
            ->orderBy('id')
            ->get();

        return [
            'count' => $this->syncLeads($leads, onlyChanges: true) + $this->syncClicks($clicks, onlyChanges: true),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    public function syncLead(Lead $lead): void
    {
        $this->assertReady();
        $this->syncLeads([$lead]);
    }

    public function syncClick(PhoneClick $click): void
    {
        $this->assertReady();
        $this->syncClicks([$click]);
    }

    public function sheetTitleFor(Lead $lead): string
    {
        return $this->sheetTitleForSource((string) $lead->traffic_source);
    }

    public function sheetTitleForSource(string $source): string
    {
        return match ($source) {
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
        /** @var array<string, list<array{id: string, row: list<string>}> > $groups */
        $groups = [];
        foreach ($leads as $lead) {
            $groups[$this->sheetTitleFor($lead)][] = [
                'id' => (string) $lead->id,
                'row' => $this->row($lead),
            ];
        }

        return $this->syncGroups($groups, $onlyChanges);
    }

    /**
     * @param  iterable<PhoneClick>  $clicks
     */
    public function syncClicks(iterable $clicks, bool $onlyChanges = false): int
    {
        /** @var array<string, list<array{id: string, row: list<string>}> > $groups */
        $groups = [];
        foreach ($clicks as $click) {
            if ($click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
                continue;
            }
            $groups[$this->sheetTitleForSource((string) $click->traffic_source)][] = [
                'id' => 'C'.$click->id,
                'row' => $this->clickRow($click),
            ];
        }

        return $this->syncGroups($groups, $onlyChanges);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function rewriteTab(string $title, array $rows): void
    {
        $spreadsheetId = $this->spreadsheetId();
        $this->sheets->ensureSheetTab($spreadsheetId, $title);
        $this->sheets->writeTabGrid($spreadsheetId, $title, array_merge([self::HEADER], $rows));
    }

    /**
     * @param  array<string, list<array{id: string, row: list<string>}>>  $groups
     */
    private function syncGroups(array $groups, bool $onlyChanges): int
    {
        $count = 0;
        foreach ($groups as $title => $group) {
            $count += $this->syncGroup($title, $group, $onlyChanges);
        }

        return $count;
    }

    /**
     * @param  list<array{id: string, row: list<string>}>  $items
     */
    private function syncGroup(string $title, array $items, bool $onlyChanges): int
    {
        if ($items === []) {
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
        foreach ($items as $item) {
            $row = $item['row'];
            $rowNumber = $idToRow[$item['id']] ?? null;
            if ($rowNumber !== null && $rowNumber > 0) {
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
            $idToRow[$item['id']] = -1;
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
            if (preg_match('/^C?\d+$/', $id) === 1) {
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
            ? CarbonImmutable::parse($lead->created_at)->setTimezone($timezone)->format('n/j/Y G:i:s')
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

    /**
     * @return list<string>
     */
    private function clickRow(PhoneClick $click): array
    {
        $touch = static function (string $key) use ($click): string {
            return trim((string) ($click->getAttribute('first_'.$key) ?? ''));
        };

        $lastPlatform = $this->regions->platformFromAttribution([
            'utm_source' => $click->utm_source,
            'gclid' => $click->gclid,
            'gbraid' => $click->gbraid,
            'wbraid' => $click->wbraid,
            'msclkid' => $click->msclkid,
        ]);
        $firstPlatform = $this->regions->platformFromAttribution([
            'utm_source' => $touch('utm_source'),
            'gclid' => $touch('gclid'),
            'gbraid' => $touch('gbraid'),
            'wbraid' => $touch('wbraid'),
            'msclkid' => $touch('msclkid'),
        ]);

        $seconds = max(0, (int) ($click->ringcentral_duration ?? 0));
        $description = 'Phone call · '.$click->ringCentralStatusLabel();
        if ($seconds > 0) {
            $description .= ' · '.intdiv($seconds, 60).'m '.($seconds % 60).'s';
        }

        return $this->pad([
            'C'.$click->id,
            $this->formatCreated($click->created_at),
            '',
            trim((string) ($click->ringCentralClientPhone() ?: $click->phone)),
            '',
            '',
            $this->cityLabel((string) $click->utm_city, $lastPlatform),
            $this->cityLabel($touch('utm_city'), $firstPlatform),
            $description,
            '',
            $click->isSpam() ? 'Spam' : $click->handlingStatusLabel(),
            $click->trafficSourceLabel(),
            trim((string) $click->page_url),
            trim((string) $click->referrer),
            trim((string) $click->utm_source),
            trim((string) $click->utm_medium),
            trim((string) $click->utm_campaign),
            trim((string) $click->utm_content),
            trim((string) $click->utm_term),
            $touch('utm_source'),
            $touch('utm_medium'),
            $touch('utm_campaign'),
            $touch('utm_content'),
            $touch('utm_term'),
            trim((string) $click->gclid),
            trim((string) $click->gbraid),
            trim((string) $click->wbraid),
            $touch('gclid'),
            $touch('gbraid'),
            $touch('wbraid'),
            trim((string) $click->msclkid),
            $touch('msclkid'),
            trim((string) $click->fbclid),
            trim((string) $click->utm_city),
        ]);
    }

    private function formatCreated(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timezone = trim((string) config('services.google_drive.timezone', 'America/Los_Angeles'));
        if ($timezone === '') {
            $timezone = 'America/Los_Angeles';
        }

        return CarbonImmutable::parse($value)->setTimezone($timezone)->format('n/j/Y G:i:s');
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
