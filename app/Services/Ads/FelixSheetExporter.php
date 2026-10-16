<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Lead;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Appends leads marked Transfered to Felix into a workbook in the shared Drive folder.
 * Every row includes a GCLID column, empty when the lead has none.
 */
final class FelixSheetExporter
{
    public function __construct(
        private readonly GoogleAdsOfflineSheetExporter $sheets,
        private readonly AppointmentSheetExporter $rows,
    ) {}

    public function spreadsheetTitle(): string
    {
        $title = trim((string) config('services.google_drive.felix_spreadsheet_title'));

        return $title !== '' ? $title : 'Deluxe Windows Transfered to Felix';
    }

    public function spreadsheetId(): string
    {
        return trim((string) config('services.google_drive.felix_spreadsheet_id'));
    }

    public function spreadsheetUrl(): string
    {
        $id = $this->spreadsheetId();

        return $id === '' ? '' : 'https://docs.google.com/spreadsheets/d/'.$id.'/edit';
    }

    public function configurationError(): ?string
    {
        if ($this->spreadsheetId() === '') {
            return 'Set GOOGLE_DRIVE_FELIX_SPREADSHEET_ID in .env.';
        }

        return $this->sheets->configurationError();
    }

    /**
     * @return array{count: int, spreadsheet_url: string}
     */
    public function exportPending(): array
    {
        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $this->assertExportColumn();

        $leads = Lead::query()
            ->where('status', Lead::STATUS_TRANSFERED_TO_FELIX)
            ->orderBy('id')
            ->get();

        return [
            'count' => $this->syncMissing($leads),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    public function onStatusChanged(Lead $lead, string $from, string $to): ?string
    {
        if ($to !== Lead::STATUS_TRANSFERED_TO_FELIX || $from === $to) {
            return null;
        }

        try {
            $this->exportLead($lead);

            return null;
        } catch (RuntimeException $e) {
            report($e);

            return $e->getMessage();
        }
    }

    public function exportLead(Lead $lead): bool
    {
        if ($lead->status !== Lead::STATUS_TRANSFERED_TO_FELIX) {
            return false;
        }

        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $this->assertExportColumn();

        return $this->syncMissing(collect([$lead]), rewriteSheet: false) > 0;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Lead>
     */
    public function pendingLeads(): \Illuminate\Database\Eloquent\Builder
    {
        return Lead::query()
            ->where('status', Lead::STATUS_TRANSFERED_TO_FELIX)
            ->whereNull('felix_sheet_exported_at')
            ->orderBy('id');
    }

    /**
     * @param  Collection<int, Lead>  $leads
     */
    private function syncMissing(Collection $leads, bool $rewriteSheet = true): int
    {
        $spreadsheetId = $this->spreadsheetId();
        $existing = $this->sheets->readRows($spreadsheetId);
        $header = $existing[0] ?? [];

        if ($header !== AppointmentSheetExporter::HEADER) {
            if (! $rewriteSheet) {
                $leads = Lead::query()
                    ->where('status', Lead::STATUS_TRANSFERED_TO_FELIX)
                    ->orderBy('id')
                    ->get();
            }

            $this->sheets->replaceLabeledRows(
                $spreadsheetId,
                AppointmentSheetExporter::HEADER,
                $leads->map(fn (Lead $lead): array => $this->rows->leadRow($lead))->all(),
            );
            $this->markExported($leads);

            return $leads->count();
        }

        $index = array_search('Lead ID', $header, true);
        $present = [];
        if ($index !== false) {
            foreach (array_slice($existing, 1) as $row) {
                $id = trim((string) ($row[$index] ?? ''));
                if ($id !== '') {
                    $present[$id] = true;
                }
            }
        }

        $missing = $leads
            ->filter(fn (Lead $lead): bool => ! isset($present[(string) $lead->id]))
            ->values();
        $this->appendLeads($missing);

        return $missing->count();
    }

    /**
     * @param  Collection<int, Lead>  $leads
     */
    private function appendLeads(Collection $leads): void
    {
        if ($leads->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($leads as $lead) {
            $rows[] = $this->rows->leadRow($lead);
        }

        $this->sheets->appendLabeledRows($this->spreadsheetId(), AppointmentSheetExporter::HEADER, $rows);
        $this->markExported($leads);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     */
    private function markExported(Collection $leads): void
    {
        if ($leads->isEmpty()) {
            return;
        }

        Lead::query()
            ->whereIn('id', $leads->pluck('id')->all())
            ->update(['felix_sheet_exported_at' => now()]);
    }

    private function assertExportColumn(): void
    {
        if (! Schema::hasColumn('leads', 'felix_sheet_exported_at')) {
            throw new RuntimeException('Run php artisan migrate. The leads table is missing felix_sheet_exported_at.');
        }
    }
}
