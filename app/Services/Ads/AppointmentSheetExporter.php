<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Lead;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Appends leads marked Appointment into the shared work-status spreadsheet.
 */
final class AppointmentSheetExporter
{
    public const HEADER = [
        'Name',
        'Phone',
        'Email',
        'City',
        'Message',
        'Page',
        'Source',
        'Created',
        'Lead ID',
    ];

    public function __construct(
        private readonly GoogleAdsOfflineSheetExporter $sheets,
    ) {}

    public function spreadsheetId(): string
    {
        return trim((string) config('services.google_drive.appointments_spreadsheet_id'));
    }

    public function spreadsheetUrl(): string
    {
        $id = $this->spreadsheetId();

        return $id === '' ? '' : 'https://docs.google.com/spreadsheets/d/'.$id.'/edit';
    }

    public function configurationError(): ?string
    {
        if ($this->spreadsheetId() === '') {
            return 'Set GOOGLE_DRIVE_APPOINTMENTS_SPREADSHEET_ID in .env.';
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

        $leads = $this->pendingLeads()->get();
        $this->appendLeads($leads);

        return [
            'count' => $leads->count(),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    public function exportLead(Lead $lead): bool
    {
        if ($lead->status !== Lead::STATUS_APPOINTMENT || $lead->appointments_sheet_exported_at !== null) {
            return false;
        }

        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $this->appendLeads(collect([$lead]));

        return true;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Lead>
     */
    public function pendingLeads(): \Illuminate\Database\Eloquent\Builder
    {
        return Lead::query()
            ->where('status', Lead::STATUS_APPOINTMENT)
            ->whereNull('appointments_sheet_exported_at')
            ->orderBy('id');
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
            $rows[] = $this->row($lead);
        }

        $this->sheets->appendLabeledRows($this->spreadsheetId(), self::HEADER, $rows);

        $now = now();
        Lead::query()
            ->whereIn('id', $leads->pluck('id')->all())
            ->update(['appointments_sheet_exported_at' => $now]);
    }

    /**
     * @return list<string>
     */
    private function row(Lead $lead): array
    {
        $timezone = trim((string) config('services.google_drive.timezone', 'America/Los_Angeles'));
        if ($timezone === '') {
            $timezone = 'America/Los_Angeles';
        }

        $created = $lead->created_at !== null
            ? CarbonImmutable::parse($lead->created_at)->setTimezone($timezone)->format('Y-m-d H:i')
            : '';

        return [
            trim((string) $lead->full_name),
            trim((string) $lead->phone),
            trim((string) $lead->email),
            trim((string) $lead->city),
            trim((string) $lead->message),
            trim((string) $lead->page_url),
            trim((string) $lead->utm_source),
            $created,
            (string) $lead->id,
        ];
    }
}
