<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RingCentralCallSyncService;
use Illuminate\Console\Command;
use Throwable;

class RingCentralSyncCallsCommand extends Command
{
    protected $signature = 'ringcentral:sync-calls
                            {--days= : Force lookback N days from now (ignores checkpoint overlap)}';

    protected $description = 'Sync inbound and outbound RingCentral calls for monitored admin phone numbers';

    public function handle(RingCentralCallSyncService $sync): int
    {
        $daysOption = $this->option('days');
        $forceDays = $daysOption !== null && $daysOption !== ''
            ? max(1, (int) $daysOption)
            : null;

        try {
            $result = $sync->sync(forceDays: $forceDays);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('RingCentral call sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'RingCentral calls synced for %s: %d fetched, %d created, %d updated.',
            $result['business_phone'],
            $result['fetched'],
            $result['created'],
            $result['updated'],
        ));
        $this->line(sprintf('Window: %s → %s', $result['from'], $result['to']));

        return self::SUCCESS;
    }
}
