<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PhoneClick;
use App\Services\Ads\LeadListSheetExporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Writes one RingCentral-confirmed phone click into the shared leads workbook.
 * Failures are reported and swallowed; the scheduled sweep writes the row again.
 */
final class SyncPhoneClickToListSheet implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $phoneClickId) {}

    public function handle(LeadListSheetExporter $exporter): void
    {
        if (! config('services.google_drive.leads_list_sync')) {
            return;
        }

        if ($exporter->configurationError() !== null) {
            return;
        }

        $click = PhoneClick::query()->find($this->phoneClickId);
        if ($click === null || $click->ringcentral_status !== PhoneClick::RINGCENTRAL_FOUND) {
            return;
        }

        try {
            $exporter->syncClick($click);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
