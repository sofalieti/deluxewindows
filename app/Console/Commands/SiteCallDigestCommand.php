<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SiteCallDigestMail;
use App\Models\PhoneClick;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SiteCallDigestCommand extends Command
{
    protected $signature = 'calls:site-digest';

    protected $description = 'Email the last 24 hours of website calls that connected';

    public function handle(): int
    {
        $windowEnd = now('America/Los_Angeles');
        $windowStart = $windowEnd->copy()->subDay();

        $recipients = array_values(array_filter(
            (array) config('services.lead_notifications.to', []),
            fn ($email) => trim((string) $email) !== '',
        ));

        if ($recipients === []) {
            $this->warn('No lead notification recipients.');

            return self::SUCCESS;
        }

        $clicks = PhoneClick::query()
            ->notSpam()
            ->where(function ($query) use ($windowStart, $windowEnd): void {
                $query->whereBetween('created_at', [$windowStart, $windowEnd])
                    ->orWhereBetween('ringcentral_call_started_at', [$windowStart, $windowEnd]);
            })
            ->get()
            ->filter(fn (PhoneClick $click) => $click->isConnectedCall())
            ->sortBy(fn (PhoneClick $click) => ($click->ringcentral_call_started_at ?? $click->created_at)?->getTimestamp() ?? 0)
            ->values();

        if ($clicks->isEmpty()) {
            $this->info('No connected site calls in this window.');

            return self::SUCCESS;
        }

        Mail::to($recipients)->send(new SiteCallDigestMail($windowStart, $windowEnd, $clicks));

        $this->info(sprintf('Sent the site-call digest (%d call(s)).', $clicks->count()));

        return self::SUCCESS;
    }
}
