<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Webflow\WebflowReferenceLinkSyncService;
use Illuminate\Console\Command;

class WebflowSyncLinksCommand extends Command
{
    protected $signature = 'webflow:sync-links';

    protected $description = 'Build reference and multireference links between imported Webflow collections.';

    public function handle(WebflowReferenceLinkSyncService $service): int
    {
        $result = $service->sync();

        $this->info('Webflow links synced: '.(int) ($result['created'] ?? 0));

        foreach (($result['by_collection'] ?? []) as $collection => $count) {
            $this->line("- {$collection}: {$count}");
        }

        return self::SUCCESS;
    }
}

