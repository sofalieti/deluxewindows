<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Mailbox\ImapMailboxService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SyncMailboxJob
{
    use Dispatchable;

    public function handle(ImapMailboxService $imap): void
    {
        $result = $imap->sync();
        Log::info('Mailbox sync finished', $result);
    }
}
