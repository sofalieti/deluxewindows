<?php

namespace App\Console\Commands;

use App\Services\Webflow\WebflowClient;
use App\Services\Webflow\WebflowCodegenService;
use App\Services\Webflow\WebflowSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RuntimeException;

class WebflowSyncCommand extends Command
{
    protected $signature = 'webflow:sync
        {--site-id= : Webflow site ID}
        {--token= : Webflow API token override}
        {--without-dom : Skip page DOM downloads}
        {--with-import : Import exported items into generated tables}
        {--with-migrate : Run migrate after generating migration files}';

    protected $description = 'Sync Webflow pages and CMS data, then generate Laravel artifacts.';

    public function handle(): int
    {
        $token = $this->option('token') ?: config('webflow.api_token');
        $siteId = $this->option('site-id') ?: config('webflow.site_id');
        $withDom = ! (bool) $this->option('without-dom');
        $withMigrate = (bool) $this->option('with-migrate');
        $withImport = (bool) $this->option('with-import');

        if (! $token) {
            throw new RuntimeException('Missing Webflow token. Pass --token or set WEBFLOW_API_TOKEN.');
        }

        $client = new WebflowClient((string) $token);

        if (! $siteId) {
            $sites = $client->listSites();
            if ($sites === []) {
                throw new RuntimeException('No sites returned by Webflow API for this token.');
            }

            $site = collect($sites)->first(fn ($s) => Str::contains(Str::lower((string) ($s['displayName'] ?? '')), 'deluxe'))
                ?: $sites[0];
            $siteId = $site['id'] ?? null;
            $this->info('Auto-selected site: '.($site['displayName'] ?? 'Unknown').' ('.$siteId.')');
        }

        if (! is_string($siteId) || $siteId === '') {
            throw new RuntimeException('Unable to resolve Webflow site ID.');
        }

        $sync = new WebflowSyncService($client);
        $result = $sync->sync($siteId, $withDom);
        $root = $result['root'];

        $this->info("Exported Webflow data to storage/app/{$root}");
        $this->line('Pages: '.$result['manifest']['pagesCount'].' | Collections: '.$result['manifest']['collectionsCount']);

        $codegen = new WebflowCodegenService();
        $generated = $codegen->generateFromExport($root);
        $this->info('Generated migrations: '.count($generated['migrations']));
        $this->info('Generated models: '.count($generated['models']));
        $this->info('Generated views: '.count($generated['views']));

        if ($withMigrate) {
            $this->call('migrate', ['--force' => true]);
        }

        if ($withImport) {
            $imported = $codegen->importIntoDatabase($root);
            $this->info('Imported collections: '.count($imported));
        }

        return self::SUCCESS;
    }
}
