<?php

namespace App\Console\Commands;

use App\Services\Webflow\WebflowCodegenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WebflowLocalFilesCommand extends Command
{
    protected $signature = 'webflow:local
        {action : generate|import|export|all}
        {--root= : Export root relative to WEBFLOW_EXPORT_DISK}
        {--with-migrate : Run migrate before import}';

    protected $description = 'Work with local Webflow JSON files without calling Webflow API.';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $root = trim((string) ($this->option('root') ?: config('webflow.export_root', 'current')), '/');
        $withMigrate = (bool) $this->option('with-migrate');

        if (! in_array($action, ['generate', 'import', 'export', 'all'], true)) {
            throw new RuntimeException('Action must be one of: generate, import, export, all.');
        }

        $service = new WebflowCodegenService();

        if (in_array($action, ['generate', 'all'], true)) {
            $generated = $service->generateFromExport($root);
            $this->info('Generated migrations: '.count($generated['migrations']));
            $this->info('Generated models: '.count($generated['models']));
            $this->info('Generated views: '.count($generated['views']));
        }

        if (in_array($action, ['import', 'all'], true)) {
            if ($withMigrate) {
                $this->call('migrate', ['--force' => true]);
            }

            $imported = $service->importIntoDatabase($root);
            $this->info('Imported collections from local files: '.count($imported));
        }

        if (in_array($action, ['export', 'all'], true)) {
            $exported = $service->exportFromDatabase($root);
            $this->info('Exported collections to local files: '.count($exported));
        }

        $disk = Storage::disk((string) config('webflow.export_disk', 'webflow_repo'));
        $this->line('Disk: '.config('webflow.export_disk', 'webflow_repo'));
        $this->line('Root: '.$disk->path($root));

        return self::SUCCESS;
    }
}
