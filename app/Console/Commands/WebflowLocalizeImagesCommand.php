<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Webflow\WebflowImageLocalizerService;
use Illuminate\Console\Command;

class WebflowLocalizeImagesCommand extends Command
{
    protected $signature = 'webflow:localize-images
        {--collection= : Process only one collection slug}
        {--dry-run : Do not write DB/files, only show potential changes}';

    protected $description = 'Download external image URLs from Webflow data and replace them with local URLs.';

    public function handle(WebflowImageLocalizerService $service): int
    {
        $collection = $this->option('collection');
        $dryRun = (bool) $this->option('dry-run');

        if (is_string($collection)) {
            $collection = trim($collection);
            $collection = $collection !== '' ? $collection : null;
        } else {
            $collection = null;
        }

        $this->info($dryRun ? 'Running in dry-run mode...' : 'Running image localization...');

        $result = $service->localize($collection, $dryRun);

        $this->line('Processed rows: '.(int) ($result['processed_rows'] ?? 0));
        $this->line('Updated rows: '.(int) ($result['updated_rows'] ?? 0));
        $this->line('Downloaded files: '.(int) ($result['downloaded_files'] ?? 0));

        foreach (($result['by_collection'] ?? []) as $slug => $stats) {
            $processed = (int) ($stats['processed'] ?? 0);
            $updated = (int) ($stats['updated'] ?? 0);
            $this->line("- {$slug}: processed {$processed}, updated {$updated}");
        }

        if (! $dryRun) {
            $this->comment('Done. If needed, run: php artisan storage:link');
        }

        return self::SUCCESS;
    }
}

