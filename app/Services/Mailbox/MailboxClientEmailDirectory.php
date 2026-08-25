<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\Contact;
use App\Models\Lead;

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
        $emails = Contact::query()
            ->whereNotNull('normalized_email')
            ->where('normalized_email', '!=', '')
            ->pluck('normalized_email');

        $leadEmails = Lead::query()
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', Lead::STATUS_SPAM);
            })
            ->whereNotNull('normalized_email')
            ->where('normalized_email', '!=', '')
            ->pluck('normalized_email');

        $set = [];
        foreach ($emails->concat($leadEmails) as $email) {
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
