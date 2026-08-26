<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\Contact;
use App\Models\ContactEmail;
use App\Models\Lead;
use Illuminate\Support\Facades\Schema;

final class MailboxClientEmailDirectory
{
    /**
     * @var list<string>
     */
    private const IGNORED_DOMAINS = [
        'deluxewindows.com',
        'notify.deluxewindows.com',
        'noreply.deluxewindows.com',
        'click.local',
    ];

    /**
     * @return array<string, true>
     */
    public function normalizedSet(): array
    {
        $contactEmails = Contact::query()
            ->get(['email', 'normalized_email'])
            ->flatMap(fn (Contact $contact) => [$contact->normalized_email, $contact->email]);

        if (Schema::hasTable('contact_emails')) {
            $contactEmails = $contactEmails->concat(
                ContactEmail::query()->get(['email', 'normalized_email'])
                    ->flatMap(fn (ContactEmail $row) => [$row->normalized_email, $row->email])
            );
        }

        $leadEmails = Lead::query()
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', Lead::STATUS_SPAM);
            })
            ->get(['email', 'normalized_email'])
            ->flatMap(fn (Lead $lead) => [$lead->normalized_email, $lead->email]);

        $set = [];
        foreach ($contactEmails->concat($leadEmails) as $email) {
            $normalized = Contact::normalizeEmail($email);
            if ($normalized !== null && ! $this->isIgnored($normalized)) {
                $set[$normalized] = true;
            }
        }

        return $set;
    }

    public function isIgnored(string $email): bool
    {
        $normalized = Contact::normalizeEmail($email);
        if ($normalized === null) {
            return true;
        }

        $at = strrpos($normalized, '@');
        $domain = $at === false ? '' : substr($normalized, $at + 1);

        return in_array($domain, self::IGNORED_DOMAINS, true);
    }
}
