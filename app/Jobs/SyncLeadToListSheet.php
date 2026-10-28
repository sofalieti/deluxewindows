<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Lead;
use App\Services\Ads\LeadListSheetExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Writes one lead into the shared leads workbook. Failures are reported and
 * swallowed so a Google outage cannot fail the public form; the scheduled
 * sweep writes the row again.
 */
final class SyncLeadToListSheet implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $leadId) {}

    public function handle(LeadListSheetExporter $exporter): void
    {
        if (! config('services.google_drive.leads_list_sync')) {
            return;
        }

        if ($exporter->configurationError() !== null) {
            return;
        }

        $lead = Lead::query()->find($this->leadId);
        if ($lead === null) {
            return;
        }

        try {
            $exporter->syncLead($lead);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
