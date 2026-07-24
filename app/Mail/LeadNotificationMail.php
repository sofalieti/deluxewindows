<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Lead $lead)
    {
    }

    public function envelope(): Envelope
    {
        $name = trim((string) $this->lead->full_name) !== '' ? $this->lead->full_name : 'Website visitor';
        $city = trim((string) $this->lead->city);

        $replyTo = [];
        if (trim((string) $this->lead->email) !== '') {
            $replyTo[] = new Address($this->lead->email, $name);
        }

        return new Envelope(
            subject: 'New lead: '.$name.($city !== '' ? ' ('.$city.')' : ''),
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        $meta = is_array($this->lead->meta) ? $this->lead->meta : [];

        return new Content(
            view: 'emails.lead-notification',
            with: [
                'lead' => $this->lead,
                'meta' => $meta,
                'adminUrl' => route('platform.leads', ['filter' => ['id' => $this->lead->id]]),
            ],
        );
    }
}
