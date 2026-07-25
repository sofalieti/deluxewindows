<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Mailbox\ImapMailboxService;
use Illuminate\Console\Command;

class MailboxSyncCommand extends Command
{
    protected $signature = 'mailbox:sync';

    protected $description = 'Sync Deluxewindows-matching emails from IMAP (read-only, never mark seen or delete)';

    public function handle(ImapMailboxService $imap): int
    {
        $result = $imap->sync();

        if ($result['ok']) {
            $this->info($result['message']);

            return self::SUCCESS;
        }

        $this->error($result['message']);

        return self::FAILURE;
    }
}
