<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

final class MailboxGmailSearch
{
    /**
     * @param  list<string>  $emails
     */
    public function forClientEmails(array $emails, int $lookbackDays): string
    {
        $parts = [];
        foreach ($emails as $email) {
            $email = strtolower(trim($email));
            if ($email === '') {
                continue;
            }
            $parts[] = 'from:'.$email;
            $parts[] = 'to:'.$email;
        }

        if ($parts === []) {
            return '';
        }

        return $this->withLookback(implode(' OR ', $parts), $lookbackDays);
    }

    /**
     * @param  list<string>  $emails
     */
    public function forSyncTargets(array $emails, int $lookbackDays): string
    {
        $parts = array_filter([
            $this->forClientEmails($emails, 0),
            $this->forLocalServices(0),
        ]);

        if ($parts === []) {
            return '';
        }

        return $this->withLookback(implode(' OR ', $parts), $lookbackDays);
    }

    public function forLocalServices(int $lookbackDays): string
    {
        return $this->withLookback(
            'from:local-services OR from:localservices OR subject:Local',
            $lookbackDays,
        );
    }

    public function toImapWhere(string $raw): string
    {
        $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $raw);

        return 'CUSTOM X-GM-RAW "'.$escaped.'"';
    }

    private function withLookback(string $query, int $lookbackDays): string
    {
        $query = trim($query);
        if ($query === '') {
            return '';
        }

        if ($lookbackDays > 0) {
            return 'newer_than:'.$lookbackDays.'d ('.$query.')';
        }

        return $query;
    }
}
