<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\LeadListSheetExporter;
use Illuminate\Console\Command;

final class SyncLeadListSheetCommand extends Command
{
    protected $signature = 'leads:sync-list-sheet {--days=} {--minutes=1440}';

    protected $description = 'Upsert website leads into the leads workbook, one tab per source';

    public function handle(LeadListSheetExporter $exporter): int
    {
        $error = $exporter->configurationError();
        if ($error !== null) {
            $this->error($error);

            return self::FAILURE;
        }

        $days = $this->option('days');

        try {
            $result = $days !== null && $days !== ''
                ? $exporter->exportCreatedWithinDays(max(1, (int) $days))
                : $exporter->syncUpdatedWithinMinutes(max(1, (int) $this->option('minutes')));
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Wrote '.$result['count'].' lead(s).');
        $this->line($result['spreadsheet_url']);

        return self::SUCCESS;
    }
}
