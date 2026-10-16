<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ads\FelixSheetExporter;
use Illuminate\Console\Command;

final class ExportFelixSheetCommand extends Command
{
    protected $signature = 'leads:export-felix-sheet';

    protected $description = 'Create the Felix workbook if needed and append Transfered to Felix leads that are not in it yet';

    public function handle(FelixSheetExporter $exporter): int
    {
        if ($exporter->configurationError() !== null) {
            $this->error($exporter->configurationError());

            return self::FAILURE;
        }

        try {
            $result = $exporter->exportPending();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Exported '.$result['count'].' lead(s).');
        $this->line($result['spreadsheet_url']);

        return self::SUCCESS;
    }
}
