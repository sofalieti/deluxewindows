<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PromotionCalendarService;
use Illuminate\Console\Command;

class ApplyPromotionCalendarCommand extends Command
{
    protected $signature = 'promotions:apply-calendar
                            {--date= : Override “today” as Y-m-d (America/Los_Angeles)}';

    protected $description = 'Set Global Promotion Title from the active calendar period';

    public function handle(PromotionCalendarService $calendar): int
    {
        $dateOption = trim((string) $this->option('date'));
        $today = $dateOption !== ''
            ? \Carbon\Carbon::parse($dateOption, 'America/Los_Angeles')->startOfDay()
            : null;

        $result = $calendar->applyCurrentPeriod($today);

        if ($result['title'] === null) {
            $this->warn('No matching promotion calendar period for today.');

            return self::SUCCESS;
        }

        if ($result['changed']) {
            $this->info(sprintf(
                'Global Promotion Title updated to “%s” (ends %s).',
                $result['title'],
                $result['end']
            ));
        } else {
            $this->line(sprintf(
                'Already on “%s” (ends %s).',
                $result['title'],
                $result['end']
            ));
        }

        return self::SUCCESS;
    }
}
