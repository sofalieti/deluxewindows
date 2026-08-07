<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\GoogleAdsOfflineSheetExporter;
use Illuminate\Console\Command;

/**
 * Creates a new Google Sheet in the configured Drive folder with yesterday's
 * (or all pending) RingCentral-confirmed Google Ads phone clicks that have a GCLID.
 * Bing / Microsoft Ads conversions are excluded (they use the Microsoft offline API).
 */
final class ExportGoogleAdsOfflineSheetCommand extends Command
{
    protected $signature = 'ads:export-google-offline-sheet
                            {--date= : Calendar day in America/Los_Angeles (YYYY-MM-DD); default yesterday}
                            {--all-pending : Export all confirmed clicks not yet written to a sheet}
                            {--dry-run : Build rows without calling Drive/Sheets or marking clicks}';

    protected $description = 'Export RingCentral-confirmed Google Ads phone clicks to a Drive spreadsheet (excludes Bing / Microsoft Ads)';

    public function handle(GoogleAdsOfflineSheetExporter $exporter): int
    {
        $allPending = (bool) $this->option('all-pending');
        $dryRun = (bool) $this->option('dry-run');
        $date = $this->option('date');
        $date = is_string($date) && $date !== '' ? $date : null;

        if (! $dryRun && ! $exporter->isConfigured()) {
            $this->error('Google Drive sheet export is not configured: '.$exporter->configurationError());

            return self::FAILURE;
        }

        try {
            $result = $exporter->export($date, $allPending, $dryRun);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $scope = $allPending ? 'all pending' : ('day '.($date ?? 'yesterday'));
        $this->info(sprintf(
            '%s: %d row(s) for %s — %s',
            $result['dry_run'] ? 'Dry run' : 'Exported',
            $result['count'],
            $scope,
            $result['title']
        ));

        if ($result['spreadsheet_url']) {
            $this->line($result['spreadsheet_url']);
        }

        if ($result['count'] === 0) {
            $this->comment('Nothing to export.');
        }

        return self::SUCCESS;
    }
}
