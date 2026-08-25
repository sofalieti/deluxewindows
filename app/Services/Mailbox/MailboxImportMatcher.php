<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\Contact;

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
