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
        $configured = trim((string) config('services.google_drive.felix_spreadsheet_id'));
        if ($configured !== '') {
            return $configured;
        }

        return $this->sheets->findOrCreatePlainSpreadsheet($this->spreadsheetTitle());
    }

    public function spreadsheetUrl(): string
    {
        $configured = trim((string) config('services.google_drive.felix_spreadsheet_id'));
        if ($configured !== '') {
            return 'https://docs.google.com/spreadsheets/d/'.$configured.'/edit';
        }

        return '';
    }

    public function configurationError(): ?string
    {
        if (trim((string) config('services.google_drive.folder_id')) === ''
            && trim((string) config('services.google_drive.felix_spreadsheet_id')) === '') {
            return 'Set GOOGLE_DRIVE_FOLDER_ID or GOOGLE_DRIVE_FELIX_SPREADSHEET_ID in .env.';
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

        $spreadsheetId = $this->spreadsheetId();
        $leads = $this->pendingLeads()->get();
        $this->appendLeads($spreadsheetId, $leads);

        return [
            'count' => $leads->count(),
            'spreadsheet_url' => 'https://docs.google.com/spreadsheets/d/'.$spreadsheetId.'/edit',
        ];
    }

    public function onStatusChanged(Lead $lead, string $from, string $to): ?string
    {
        if ($to !== Lead::STATUS_TRANSFERED_TO_FELIX || $from === $to || $lead->felix_sheet_exported_at !== null) {
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
        if ($lead->status !== Lead::STATUS_TRANSFERED_TO_FELIX || $lead->felix_sheet_exported_at !== null) {
            return false;
        }

        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $this->assertExportColumn();
        $this->appendLeads($this->spreadsheetId(), collect([$lead]));

        return true;
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
    private function appendLeads(string $spreadsheetId, Collection $leads): void
    {
        if ($leads->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($leads as $lead) {
            $rows[] = $this->rows->leadRow($lead);
        }

        $this->sheets->appendLabeledRows($spreadsheetId, AppointmentSheetExporter::HEADER, $rows);

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
