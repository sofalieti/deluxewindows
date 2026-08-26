<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\Contact;
use App\Models\MailboxMessage;

final class MailboxImportMatcher
{
    /**
     * @param  array<string, true>  $clientEmails
     * @param  list<string>  $messageEmails
     */
    public function shouldImport(
        string $fromName,
        string $fromEmail,
        string $subject,
        array $messageEmails,
        array $clientEmails,
    ): bool {
        if ($this->isLocalServicesByGoogle($fromName, $fromEmail, $subject)) {
            return true;
        }

        return $this->matchesClient($messageEmails, $clientEmails);
    }

    /**
     * Inbound only when the client wrote it, or Local Services by Google.
     * Everything else (us, other mailboxes, third parties CC'd with the client) is outbound.
     *
     * @param  array<string, true>  $clientEmails
     */
    public function resolveDirection(
        string $fromName,
        string $fromEmail,
        string $subject,
        array $clientEmails,
    ): string {
        if ($this->isLocalServicesByGoogle($fromName, $fromEmail, $subject)) {
            return MailboxMessage::DIRECTION_INBOUND;
        }

        $from = Contact::normalizeEmail($fromEmail);
        if ($from !== null && isset($clientEmails[$from])) {
            return MailboxMessage::DIRECTION_INBOUND;
        }

        return MailboxMessage::DIRECTION_OUTBOUND;
    }

    /**
     * Direction relative to one client address (for lead list mail stats).
     */
    public function resolveDirectionForEmail(
        string $fromName,
        string $fromEmail,
        string $subject,
        ?string $clientEmail,
    ): string {
        $email = Contact::normalizeEmail($clientEmail);
        if ($email === null) {
            return MailboxMessage::DIRECTION_OUTBOUND;
        }

        return $this->resolveDirection($fromName, $fromEmail, $subject, [$email => true]);
    }

    public function isLocalServicesByGoogle(string $fromName, string $fromEmail, string $subject): bool
    {
        $fromName = strtolower($fromName);
        $fromEmail = strtolower(trim($fromEmail));
        $subject = strtolower($subject);

        if (str_contains($fromName, 'local services')) {
            return true;
        }

        if (str_contains($fromEmail, 'local-services') || str_contains($fromEmail, 'localservices')) {
            return true;
        }

        $isGoogleSender = str_ends_with($fromEmail, '@google.com')
            || str_ends_with($fromEmail, '@googlemail.com')
            || str_contains($fromEmail, '.google.com');

        return $isGoogleSender && str_contains($subject, 'local services');
    }

    /**
     * @param  list<string>  $messageEmails
     * @param  array<string, true>  $clientEmails
     */
    public function matchesClient(array $messageEmails, array $clientEmails): bool
    {
        if ($clientEmails === []) {
            return false;
        }

        foreach ($messageEmails as $email) {
            $normalized = Contact::normalizeEmail($email);
            if ($normalized !== null && isset($clientEmails[$normalized])) {
                return true;
            }
        }

        return false;
    }
}
