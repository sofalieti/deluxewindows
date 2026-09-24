<?php

declare(strict_types=1);

namespace App\Services\Ads;

use App\Models\Lead;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
        'GCLID',
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

        $this->assertExportColumn();

        $leads = $this->eligibleLeads($this->pendingLeads()->get());
        $this->appendLeads($leads);

        return [
            'count' => $leads->count(),
            'spreadsheet_url' => $this->spreadsheetUrl(),
        ];
    }

    /**
     * Send the lead when its status has just become Appointment.
     * Returns an error message when the sheet write fails, otherwise null.
     */
    public function onStatusChanged(Lead $lead, string $from, string $to): ?string
    {
        if ($to !== Lead::STATUS_APPOINTMENT || $from === $to || $lead->appointments_sheet_exported_at !== null) {
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
        if ($lead->status !== Lead::STATUS_APPOINTMENT || $lead->appointments_sheet_exported_at !== null || ! $this->shouldExport($lead)) {
            return false;
        }

        $error = $this->configurationError();
        if ($error !== null) {
            throw new RuntimeException($error);
        }

        $this->assertExportColumn();

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

    public function shouldExport(Lead $lead): bool
    {
        return $this->resolvedGclid($lead) !== null && ! $this->isBingLead($lead);
    }

    /**
     * @param  Collection<int, Lead>  $leads
     * @return Collection<int, Lead>
     */
    private function eligibleLeads(Collection $leads): Collection
    {
        return $leads->filter(fn (Lead $lead): bool => $this->shouldExport($lead))->values();
    }

    public function resolvedGclid(Lead $lead): ?string
    {
        $last = $lead->metaValue('gclid');
        if ($last !== '') {
            return $last;
        }

        $first = data_get($lead->meta, 'first_touch.gclid');
        if ($first === null || is_array($first)) {
            return null;
        }

        $first = trim((string) $first);

        return $first !== '' ? $first : null;
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
            $this->cell($lead->full_name),
            $this->cell($lead->phone),
            $this->cell($lead->email),
            $this->cell($lead->city),
            $this->cell($lead->message),
            $this->cell($lead->page_url),
            $this->cell($lead->utm_source),
            (string) $this->resolvedGclid($lead),
            $created,
            (string) $lead->id,
        ];
    }

    private function assertExportColumn(): void
    {
        if (! Schema::hasColumn('leads', 'appointments_sheet_exported_at')) {
            throw new RuntimeException('Run php artisan migrate. The leads table is missing appointments_sheet_exported_at.');
        }
    }

    private function cell(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));
        if ($text === '') {
            return '';
        }

        if (function_exists('mb_scrub')) {
            return mb_scrub($text, 'UTF-8');
        }

        $clean = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return is_string($clean) ? $clean : '';
    }

    private function isBingLead(Lead $lead): bool
    {
        if (($lead->traffic_source ?? '') === 'microsoft_ads') {
            return true;
        }

        if ($lead->metaValue('msclkid') !== '') {
            return true;
        }

        $firstMsclkid = data_get($lead->meta, 'first_touch.msclkid');
        if (is_string($firstMsclkid) && trim($firstMsclkid) !== '') {
            return true;
        }

        $source = strtolower(trim((string) $lead->utm_source));

        return $source === 'msn'
            || str_contains($source, 'bing')
            || str_contains($source, 'microsoft');
    }
}
