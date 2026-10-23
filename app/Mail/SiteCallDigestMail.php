<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PhoneClick;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Collection;
use Illuminate\Queue\SerializesModels;

class SiteCallDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, PhoneClick>  $clicks
     */
    public function __construct(
        public CarbonInterface $windowStart,
        public CarbonInterface $windowEnd,
        public Collection $clicks,
    ) {}

    public function envelope(): Envelope
    {
        $day = $this->windowEnd->timezone('America/Los_Angeles')->format('M j, Y');

        return new Envelope(
            subject: 'Connected site calls · '.$day.' ('.$this->clicks->count().')',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.site-call-digest',
            with: [
                'descriptions' => $this->clicks->mapWithKeys(
                    fn (PhoneClick $click) => [$click->id => $this->descriptionFor($click)]
                ),
            ],
        );
    }

    public function descriptionFor(PhoneClick $click): string
    {
        $overview = trim((string) $click->ringCentralCall()?->transcriptOverview());
        if ($overview !== '') {
            return $overview;
        }

        return match ((string) $click->ringcentral_status) {
            PhoneClick::RINGCENTRAL_FOUND => 'RingCentral matched this call. A written summary is not ready yet.',
            PhoneClick::RINGCENTRAL_NO_CALL => 'Someone tapped the number on the site. RingCentral did not find a call.',
            PhoneClick::RINGCENTRAL_PENDING, PhoneClick::RINGCENTRAL_NOT_CHECKED => 'Someone tapped the number on the site. RingCentral has not matched it yet.',
            PhoneClick::RINGCENTRAL_ERROR => 'Someone tapped the number on the site. The RingCentral check failed.',
            default => 'Someone tapped the number on the site.',
        };
    }
}
