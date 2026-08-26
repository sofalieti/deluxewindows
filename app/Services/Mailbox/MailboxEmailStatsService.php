<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\Contact;
use App\Models\MailboxMessage;
use Illuminate\Support\Facades\Schema;

class MailboxEmailStatsService
{
    public function __construct(
        private readonly MailboxImportMatcher $matcher,
    ) {
    }

    /**
     * @param  iterable<int, string|null>  $emails
     * @return array<string, array{inbound: int, outbound: int, last_direction: ?string}>
     */
    public function statsForEmails(iterable $emails): array
    {
        $wanted = [];
        foreach ($emails as $email) {
            $key = Contact::normalizeEmail($email);
            if ($key !== null) {
                $wanted[$key] = true;
            }
        }

        if ($wanted === [] || ! Schema::hasTable('mailbox_messages')) {
            return [];
        }

        $keys = array_keys($wanted);
        $stats = [];
        foreach ($keys as $key) {
            $stats[$key] = $this->emptyStats();
        }

        $messages = MailboxMessage::query()
            ->where(function ($query) use ($keys): void {
                foreach ($keys as $index => $email) {
                    $like = '%'.addcslashes($email, '%_\\').'%';
                    $clause = function ($inner) use ($email, $like): void {
                        $inner->whereJsonContains('participant_emails', $email)
                            ->orWhereRaw('LOWER(from_email) = ?', [$email])
                            ->orWhere('to', 'like', $like)
                            ->orWhere('cc', 'like', $like);
                    };
                    if ($index === 0) {
                        $query->where($clause);
                    } else {
                        $query->orWhere($clause);
                    }
                }
            })
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->get(['direction', 'sent_at', 'from_email', 'from_name', 'subject', 'participant_emails', 'to', 'cc']);

        foreach ($messages as $message) {
            foreach ($keys as $email) {
                if (! $this->messageTouchesEmail($message, $email)) {
                    continue;
                }

                $direction = $this->matcher->resolveDirectionForEmail(
                    (string) ($message->from_name ?? ''),
                    (string) ($message->from_email ?? ''),
                    (string) ($message->subject ?? ''),
                    $email,
                );

                if ($direction === MailboxMessage::DIRECTION_INBOUND) {
                    $stats[$email]['inbound']++;
                } else {
                    $stats[$email]['outbound']++;
                }

                if ($stats[$email]['last_direction'] === null) {
                    $stats[$email]['last_direction'] = $direction;
                }
            }
        }

        return $stats;
    }

    /**
     * @param  array<string, array{inbound: int, outbound: int, last_direction: ?string}>  $stats
     * @return array{inbound: int, outbound: int, last_direction: ?string}
     */
    public function lookup(array $stats, ?string $email): array
    {
        $key = Contact::normalizeEmail($email);

        return ($key !== null && isset($stats[$key])) ? $stats[$key] : $this->emptyStats();
    }

    /**
     * @return array{inbound: int, outbound: int, last_direction: ?string}
     */
    public function emptyStats(): array
    {
        return [
            'inbound' => 0,
            'outbound' => 0,
            'last_direction' => null,
        ];
    }

    private function messageTouchesEmail(MailboxMessage $message, string $email): bool
    {
        if (Contact::normalizeEmail($message->from_email) === $email) {
            return true;
        }

        $participants = $message->participant_emails;
        if (is_array($participants)) {
            foreach ($participants as $participant) {
                if (Contact::normalizeEmail($participant) === $email) {
                    return true;
                }
            }
        }

        $haystack = strtolower((string) $message->to.' '.(string) $message->cc);

        return $haystack !== ' ' && str_contains($haystack, $email);
    }
}
