<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\CrmTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CrmTaskDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, CrmTask>  $overdue
     * @param  Collection<int, CrmTask>  $today
     */
    public function __construct(
        public User $manager,
        public Collection $overdue,
        public Collection $today,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'CRM tasks for '.$this->manager->name.': '.$this->overdue->count().' overdue, '.$this->today->count().' due today',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.crm-task-digest',
            with: [
                'manager' => $this->manager,
                'overdue' => $this->overdue,
                'today' => $this->today,
                'workUrl' => route('platform.crm.work'),
            ],
        );
    }
}
