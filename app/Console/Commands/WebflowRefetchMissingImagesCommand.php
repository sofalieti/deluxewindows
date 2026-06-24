<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Webflow\WebflowMissingImageRefetchService;
use Illuminate\Console\Command;

class WebflowRefetchMissingImagesCommand extends Command
{
    protected $signature = 'webflow:refetch-missing-images
        {--collection= : Process only one collection slug}
        {--dry-run : Only report what is missing and what would be fetched}
        {--list-missing : Only list every missing reference (all fields, multi-value too); no API, no downloads}
        {--export : Re-export local Webflow JSON files after rewriting the database}';

    protected $description = 'Find DB image references whose local file is missing, re-fetch a fresh URL from Webflow (by fileId) and download it.';

    public function handle(WebflowMissingImageRefetchService $service): int
    {
        $collection = $this->option('collection');
        $collection = is_string($collection) && trim($collection) !== '' ? trim($collection) : null;
        $dryRun = (bool) $this->option('dry-run');
        $listOnly = (bool) $this->option('list-missing');

        if ($listOnly) {
            $this->warn('LIST ONLY — enumerating every missing reference (no API, no downloads).');
        } elseif ($dryRun) {
            $this->warn('DRY RUN — nothing will be downloaded or written.');
        }

        $stats = $service->run($collection, $dryRun, $listOnly, function (string $message): void {
            $this->line($message);
        });

        $this->newLine();
        $this->info('Done.');
        $this->line('Missing references found: '.(int) $stats['missing']);
        $this->line('Downloaded directly:     '.(int) $stats['downloaded_direct']);
        $this->line('Re-fetched from Webflow: '.(int) $stats['refetched']);
        if ($dryRun) {
            $this->line('Would download directly:  '.(int) $stats['would_download']);
            $this->line('Would refetch from API:   '.(int) $stats['would_refetch']);
        }
        $this->line('Rows updated:            '.(int) $stats['rows_updated']);
        $this->line('Unresolved (still missing): '.(int) $stats['unresolved']);

        if (! $dryRun && ! $listOnly && (bool) $this->option('export')) {
            $this->newLine();
            $this->info('Re-exporting local Webflow JSON files...');
            $this->call('webflow:local', ['action' => 'export']);
        }

        return (int) $stats['unresolved'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
