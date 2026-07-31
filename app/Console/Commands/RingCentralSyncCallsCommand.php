<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RingCentralCallSyncService;
use Illuminate\Console\Command;
use Throwable;

class RingCentralSyncCallsCommand extends Command
{
    protected $signature = 'ringcentral:sync-calls';

    protected $description = 'Sync inbound and outbound RingCentral calls for the current admin phone number';

    public function handle(RingCentralCallSyncService $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->error('RingCentral call sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'RingCentral calls synced for %s: %d created, %d updated.',
            $result['business_phone'],
            $result['created'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }
}
