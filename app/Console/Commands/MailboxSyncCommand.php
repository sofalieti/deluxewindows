<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Mailbox\ImapMailboxService;
use Illuminate\Console\Command;

class MailboxSyncCommand extends Command
{
    protected $signature = 'mailbox:sync {--days= : Prefer mail newer than this many days} {--seconds=150}';

    protected $description = 'Sync client and Local Services emails from IMAP (read-only, never mark seen or delete)';

    public function handle(ImapMailboxService $imap): int
    {
        $days = $this->option('days');
        $result = $imap->sync(
            maxSeconds: max(5, (int) $this->option('seconds')),
            lookbackDays: $days !== null && $days !== '' ? max(0, (int) $days) : null,
        );

        if ($result['ok']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
