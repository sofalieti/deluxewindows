<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\MatchPhoneClickToRingCentral;
use App\Models\PhoneClick;
use App\Services\RingCentralCallLogService;
use Illuminate\Console\Command;

class MatchPendingPhoneClicksCommand extends Command
{
    protected $signature = 'calls:match-pending';

    protected $description = 'Keep matching phone clicks that are still checking, and close ones whose lookup window has ended';

    public function handle(RingCentralCallLogService $ringCentral): int
    {
        $lookupMinutes = max(3, (int) config('services.ringcentral.lookup_window_minutes', 10));
        $cutoff = now()->subMinutes($lookupMinutes);

        $clicks = PhoneClick::query()
            ->notSpam()
            ->where(function ($query) use ($cutoff): void {
                $query->where('ringcentral_status', PhoneClick::RINGCENTRAL_PENDING)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->where('ringcentral_status', PhoneClick::RINGCENTRAL_ERROR)
                            ->where('created_at', '>=', $cutoff);
                    });
            })
            ->where(function ($query): void {
                $query->whereNull('ringcentral_checked_at')
                    ->orWhere('ringcentral_checked_at', '<=', now()->subSeconds(45));
            })
            ->orderBy('id')
            ->limit(20)
            ->get();

        foreach ($clicks as $click) {
            (new MatchPhoneClickToRingCentral($click->id))->handle($ringCentral);
        }

        $this->info(sprintf('Checked %d pending phone click(s).', $clicks->count()));

        return self::SUCCESS;
    }
}
