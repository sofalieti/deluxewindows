<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PhoneClick;
use App\Services\ContactFromPhoneClickService;
use Illuminate\Console\Command;

class BackfillPhoneClickContactsCommand extends Command
{
    protected $signature = 'crm:backfill-phone-click-contacts
                            {--limit=500 : Max clicks to scan}';

    protected $description = 'Link existing phone clicks to contacts by normalized phone';

    public function handle(ContactFromPhoneClickService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $linked = 0;
        $scanned = 0;

        PhoneClick::query()
            ->whereNull('contact_id')
            ->notSpam()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->each(function (PhoneClick $click) use ($service, &$linked, &$scanned): void {
                $scanned++;
                if ($service->attachNewClick($click) !== null) {
                    $linked++;
                }
            });

        $this->info(sprintf('Scanned %d phone click(s), linked %d.', $scanned, $linked));

        return self::SUCCESS;
    }
}
