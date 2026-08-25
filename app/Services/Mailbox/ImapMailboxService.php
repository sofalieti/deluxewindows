<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxMessage;
use App\Models\MailboxSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

class ImapMailboxService
{
    public function __construct(
        private readonly MailboxSettingsService $settings,
        private readonly GoogleMailboxOAuthService $oauth,
        private readonly MailboxImportMatcher $matcher,
        private readonly MailboxClientEmailDirectory $clients,
        private readonly MailboxGmailSearch $gmailSearch,
        private readonly MailboxFolderPicker $folders,
    ) {
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function testConnection(?MailboxSetting $setting = null): array
    {
        $setting ??= $this->settings->get();

        try {
            $client = $this->connect($setting);
            $folder = $client->getFolder($setting->folder ?: 'INBOX');
            if ($folder === null) {
                $client->disconnect();

                return ['ok' => false, 'message' => 'Folder not found: '.($setting->folder ?: 'INBOX')];
            }
            $client->disconnect();

            return ['ok' => true, 'message' => 'IMAP connection successful.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync matching messages. Never marks as read, never deletes.
     *
     * @return array{ok: bool, imported: int, skipped: int, message: string}
     */
    public function sync(?MailboxSetting $setting = null, int $maxSeconds = 150, ?int $lookbackDays = null): array
    {
        $setting ??= $this->settings->get();
        $maxSeconds = max(5, $maxSeconds);

        if (! $setting->enabled) {
            return ['ok' => false, 'imported' => 0, 'skipped' => 0, 'message' => 'Mailbox sync is disabled.'];
        }

        if (! $this->hasCredentials($setting)) {
            return ['ok' => false, 'imported' => 0, 'skipped' => 0, 'message' => 'Connect Google mailbox or configure IMAP credentials first.'];
        }

        $imported = 0;
        $skipped = 0;
        $more = false;

        try {
            @set_time_limit($maxSeconds + 20);
            $started = microtime(true);
            $client = $this->connect($setting);
            $clientEmails = $this->clients->normalizedSet();
            $cursors = is_array($setting->folder_cursors) ? $setting->folder_cursors : [];
            $emailList = array_keys($clientEmails);
            $lookbackDays = $lookbackDays ?? $this->currentLookbackDays($cursors, $maxSeconds);
            $foldersUsed = [];
            $listedFolders = [];
            $candidates = 0;
            $connectedEmail = strtolower($this->oauth->accountEmail($setting));
            [$syncFolders, $listedFolders] = $this->foldersToSync($client, $setting, $maxSeconds < 60);

            foreach ($syncFolders as $folderName => $folder) {
                if ((microtime(true) - $started) > $maxSeconds) {
                    $more = true;
                    break;
                }

                $foldersUsed[] = $folderName;
                $isSent = $this->folders->isSent($folderName);
                $knownUids = MailboxMessage::query()
                    ->where('folder', $folderName)
                    ->pluck('imap_uid')
                    ->flip()
                    ->all();

                $found = $this->fetchRecentMatchingMessages(
                    $folder,
                    $emailList,
                    $lookbackDays,
                    $started,
                    $maxSeconds,
                    $maxSeconds < 60,
                );
                $candidates += $found['targeted']->count() + $found['broad']->count();

                foreach (['targeted' => true, 'broad' => false] as $bucket => $force) {
                    foreach ($this->newestFirst($found[$bucket]) as $message) {
                        /** @var Message $message */
                        $uid = (int) $message->getUid();
                        if ($uid > 0 && isset($knownUids[$uid])) {
                            $skipped++;
                            continue;
                        }

                        $result = $this->importMessage(
                            $message,
                            $folderName,
                            $clientEmails,
                            $isSent,
                            $connectedEmail,
                            $force,
                            $maxSeconds >= 90,
                        );
                        if ($result === 'imported') {
                            $imported++;
                            if ($uid > 0) {
                                $knownUids[$uid] = true;
                            }
                        } else {
                            $skipped++;
                        }
                    }
                }
            }

            unset($cursors['email_offset']);
            $cursors['lookback_days'] = $lookbackDays;
            if (! $more && $lookbackDays > 0) {
                $cursors['lookback_days'] = $this->nextLookbackDays($lookbackDays);
                $more = $cursors['lookback_days'] !== $lookbackDays;
            }

            $client->disconnect();

            $setting->folder_cursors = $cursors;
            $setting->last_sync_at = now();
            $window = $lookbackDays > 0 ? 'last '.$lookbackDays.' days' : 'all time';
            $message = 'Account '.$connectedEmail.' · '.$window.' · folders '.implode(', ', $foldersUsed ?: ['none'])
                .'. Searched '.count($emailList).' client emails, IMAP found '.$candidates
                .' · Synced: '.$imported.' new, '.$skipped.' skipped.';
            if ($listedFolders !== []) {
                $message .= ' IMAP sees: '.implode(', ', array_slice($listedFolders, 0, 12));
            }
            if ($emailList === []) {
                $message = 'No client emails found on leads/contacts. Add emails to cards, then sync again.';
            } elseif ($more) {
                $message .= ' Refresh and Sync again to import the next batch.';
            }
            if ($imported === 0 && $candidates === 0 && $emailList !== []) {
                $message .= ' If this Gmail is not the inbox where clients write, connect that account instead.';
            }
            $setting->last_error = $imported === 0 ? $message : null;
            $setting->save();
            $this->settings->forgetCache();

            return [
                'ok' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => $message,
            ];
        } catch (Throwable $e) {
            Log::warning('Mailbox IMAP sync failed', ['error' => $e->getMessage()]);

            $setting->last_error = $e->getMessage();
            $setting->save();
            $this->settings->forgetCache();

            return [
                'ok' => false,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => $e->getMessage(),
            ];
        }
    }

    private function connect(MailboxSetting $setting): Client
    {
        $cm = new ClientManager([
            'options' => [
                'fetch' => IMAP::FT_PEEK,
            ],
        ]);

        $username = $this->oauth->accountEmail($setting);
        $config = [
            'host' => $setting->imap_host ?: 'imap.gmail.com',
            'port' => (int) ($setting->imap_port ?: 993),
            'encryption' => $setting->imap_encryption ?: 'ssl',
            'validate_cert' => true,
            'username' => $username,
            'protocol' => 'imap',
        ];

        if ($setting->usesOAuth() || $this->oauth->isConnected($setting)) {
            $config['password'] = $this->oauth->getValidAccessToken($setting);
            $config['authentication'] = 'oauth';
        } else {
            $config['password'] = (string) $setting->password;
            $config['authentication'] = null;
        }

        $client = $cm->make($config);
        $client->connect();

        return $client;
    }

    private function hasCredentials(MailboxSetting $setting): bool
    {
        if ($this->oauth->isConnected($setting)) {
            return true;
        }

        return trim((string) $setting->username) !== ''
            && trim((string) $setting->password) !== '';
    }

    /**
     * @param  array<string, true>  $clientEmails
     * @return 'imported'|'skipped'
     */
    private function importMessage(
        Message $message,
        string $folderName,
        array $clientEmails,
        bool $isSent,
        string $connectedEmail = '',
        bool $force = false,
        bool $fetchBody = false,
    ): string {
        $uid = (int) $message->getUid();
        if ($uid <= 0) {
            return 'skipped';
        }

        $exists = MailboxMessage::query()
            ->where('folder', $folderName)
            ->where('imap_uid', $uid)
            ->exists();
        if ($exists) {
            return 'skipped';
        }

        $messageId = $this->attrString($message->getMessageId());
        if ($messageId !== '' && MailboxMessage::query()->where('message_id', $messageId)->exists()) {
            return 'skipped';
        }

        [$fromEmail, $fromName] = $this->firstAddress($message->getFrom());
        $subject = $this->attrString($message->getSubject());
        $messageEmails = $this->messageEmails($message);

        if (! $force && ! $this->matcher->shouldImport($fromName, $fromEmail, $subject, $messageEmails, $clientEmails)) {
            return 'skipped';
        }

        $fromNormalized = strtolower(trim($fromEmail));
        $outbound = $isSent || ($connectedEmail !== '' && $fromNormalized === $connectedEmail);

        if ($fetchBody) {
            $this->ensureMessageBody($message);
        }
        $this->storeMessage(
            $message,
            $folderName,
            $outbound ? MailboxMessage::DIRECTION_OUTBOUND : MailboxMessage::DIRECTION_INBOUND,
        );

        return 'imported';
    }

    /**
     * @param  list<string>  $emails
     * @return array{targeted: Collection, broad: Collection}
     */
    private function fetchRecentMatchingMessages(
        mixed $folder,
        array $emails,
        int $lookbackDays,
        float $started,
        int $maxSeconds,
        bool $shortRun,
    ): array {
        $targeted = collect();
        $since = $lookbackDays > 0 ? now()->subDays($lookbackDays) : null;
        $limit = $shortRun ? 80 : 120;

        $chunks = array_chunk($emails, 30);
        if ($chunks === []) {
            $chunks = [[]];
        }
        foreach ($chunks as $index => $batch) {
            $raw = $index === 0
                ? $this->gmailSearch->forSyncTargets($batch, $lookbackDays)
                : $this->gmailSearch->forClientEmails($batch, $lookbackDays);
            if ($raw === '') {
                continue;
            }
            $targeted = $targeted->merge($this->safeSearch($folder, function ($query) use ($raw, $since, $limit): void {
                $query->where($this->gmailSearch->toImapWhere($raw));
                $this->preferRecent($query, $since, $limit);
            }));
        }

        if ($targeted->isEmpty() && (microtime(true) - $started) < $maxSeconds - 2) {
            foreach (['local-services', 'localservices'] as $from) {
                $targeted = $targeted->merge($this->safeSearch($folder, function ($query) use ($from, $since): void {
                    $query->whereFrom($from);
                    $this->preferRecent($query, $since, 30);
                }));
            }
            foreach (array_slice($emails, 0, $shortRun ? 15 : 40) as $email) {
                if ((microtime(true) - $started) > $maxSeconds - 1) {
                    break;
                }
                $targeted = $targeted->merge($this->safeSearch($folder, function ($query) use ($email, $since): void {
                    $query->whereFrom($email);
                    $this->preferRecent($query, $since, 15);
                }));
                $targeted = $targeted->merge($this->safeSearch($folder, function ($query) use ($email, $since): void {
                    $query->whereTo($email);
                    $this->preferRecent($query, $since, 15);
                }));
            }
        }

        $broad = collect();
        if (! $shortRun) {
            $broad = $this->safeSearch($folder, function ($query) use ($since): void {
                $this->preferRecent($query, $since, 40);
            });
        }

        $unique = fn (mixed $message): int => (int) $message->getUid();

        return [
            'targeted' => $targeted->unique($unique)->values(),
            'broad' => $broad->unique($unique)->values(),
        ];
    }

    private function preferRecent(mixed $query, mixed $since, int $limit): void
    {
        if ($since !== null && method_exists($query, 'since')) {
            $query->since($since);
        }
        if (method_exists($query, 'setFetchOrderDesc')) {
            $query->setFetchOrderDesc();
        }
        $query->limit($limit);
    }

    /**
     * @param  array<string, mixed>  $cursors
     */
    private function currentLookbackDays(array $cursors, int $maxSeconds): int
    {
        $stored = (int) ($cursors['lookback_days'] ?? 0);
        if ($maxSeconds < 40) {
            return $stored > 0 && $stored <= 45 ? $stored : 45;
        }

        return $stored > 0 || array_key_exists('lookback_days', $cursors) ? $stored : 45;
    }

    private function nextLookbackDays(int $lookbackDays): int
    {
        return match (true) {
            $lookbackDays <= 45 => 180,
            $lookbackDays <= 180 => 800,
            default => 0,
        };
    }

    /**
     * @param  callable(\Webklex\PHPIMAP\Query\WhereQuery): mixed  $configure
     */
    private function safeSearch(mixed $folder, callable $configure): Collection
    {
        try {
            $query = $folder->query()
                ->leaveUnread()
                ->setFetchBody(false);
            if (method_exists($query, 'setFetchFlags')) {
                $query->setFetchFlags(false);
            }
            if (method_exists($query, 'setFetchAttachment')) {
                $query->setFetchAttachment(false);
            }
            $configure($query);

            return collect($query->get());
        } catch (Throwable $e) {
            Log::warning('Mailbox IMAP search failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function foldersToSync(Client $client, MailboxSetting $setting, bool $shortRun = false): array
    {
        $inbox = trim((string) ($setting->folder ?: 'INBOX')) ?: 'INBOX';
        $wanted = [];
        $listed = [];

        try {
            foreach ($this->walkFolders($client->getFolders()) as $folder) {
                $full = $this->folders->name($folder);
                if ($full === '') {
                    continue;
                }
                $listed[] = $full;
                if ($this->folders->shouldSync($full, $inbox)) {
                    $wanted[$full] = $folder;
                }
            }
        } catch (Throwable $e) {
            Log::info('Mailbox could not list extra IMAP folders', ['error' => $e->getMessage()]);
        }

        if ($wanted === []) {
            try {
                $fallback = $client->getFolder($inbox);
                if ($fallback !== null) {
                    $wanted[$inbox] = $fallback;
                }
            } catch (Throwable) {
                // listed names still help diagnose empty IMAP.
            }
        }

        $hasAllMail = false;
        foreach (array_keys($wanted) as $name) {
            if ($this->folders->isAllMail($name)) {
                $hasAllMail = true;
                break;
            }
        }
        if ($hasAllMail) {
            $wanted = array_filter(
                $wanted,
                fn (mixed $folder, string $name): bool => $this->folders->isAllMail($name) || $this->folders->isSent($name),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        uksort($wanted, fn (string $left, string $right): int => $this->folders->priority($right) <=> $this->folders->priority($left));

        if ($shortRun && ! $hasAllMail && count($wanted) > 3) {
            $wanted = array_slice($wanted, 0, 3, true);
        }

        return [$wanted, array_values(array_unique($listed))];
    }

    /**
     * @return list<mixed>
     */
    private function walkFolders(mixed $folders): array
    {
        $out = [];
        if (! is_iterable($folders)) {
            return $out;
        }

        foreach ($folders as $folder) {
            $out[] = $folder;
            $children = is_object($folder) ? ($folder->children ?? null) : null;
            if (is_iterable($children)) {
                $out = array_merge($out, $this->walkFolders($children));
            }
        }

        return $out;
    }

    private function newestFirst(Collection $messages): Collection
    {
        return $messages
            ->sortByDesc(function (mixed $message): int {
                try {
                    $date = $message->getDate();
                    if ($date instanceof \Webklex\PHPIMAP\Attribute) {
                        $carbon = $date->toDate();
                        if ($carbon instanceof \DateTimeInterface) {
                            return $carbon->getTimestamp();
                        }
                    }
                } catch (Throwable) {
                    // Fall back to UID, which Gmail assigns in arrival order.
                }

                return (int) $message->getUid();
            })
            ->values();
    }

    private function ensureMessageBody(Message $message): void
    {
        try {
            if (method_exists($message, 'parseAll')) {
                $message->parseAll();
            } elseif (method_exists($message, 'parse')) {
                $message->parse();
            }
        } catch (Throwable) {
            // Header-only fetch still stores subject/from; body may stay empty.
        }
    }

    /**
     * @return list<string>
     */
    private function messageEmails(Message $message): array
    {
        $emails = [];
        foreach ([$message->getFrom(), $message->getTo(), $message->getCc()] as $addresses) {
            foreach ($this->addressList($addresses) as $item) {
                $mail = trim((string) ($item['mail'] ?? ''));
                if ($mail !== '') {
                    $emails[] = $mail;
                }
            }
        }

        return array_values(array_unique($emails));
    }

    private function storeMessage(Message $message, string $folderName, string $direction = MailboxMessage::DIRECTION_INBOUND): MailboxMessage
    {
        [$fromEmail, $fromName] = $this->firstAddress($message->getFrom());
        $to = $this->addressesToString($message->getTo());
        $cc = $this->addressesToString($message->getCc());

        $subject = $this->attrString($message->getSubject());
        $text = (string) ($message->getTextBody() ?? '');
        $html = (string) ($message->getHTMLBody() ?? '');

        $sentAt = null;
        try {
            $dateAttr = $message->getDate();
            if ($dateAttr instanceof \Webklex\PHPIMAP\Attribute) {
                $sentAt = $dateAttr->toDate();
            }
        } catch (Throwable) {
            $sentAt = null;
        }

        $messageId = $this->attrString($message->getMessageId());
        $inReplyTo = $this->attrString($message->getInReplyTo());

        $snippetSource = $text !== '' ? $text : strip_tags($html);
        $snippet = Str::limit(trim(preg_replace('/\s+/', ' ', $snippetSource) ?? ''), 480);

        $participants = [];
        foreach ($this->messageEmails($message) as $email) {
            $normalized = \App\Models\Contact::normalizeEmail($email);
            if ($normalized !== null) {
                $participants[$normalized] = true;
            }
        }

        $record = MailboxMessage::query()->create([
            'direction' => $direction,
            'folder' => $folderName,
            'imap_uid' => (int) $message->getUid(),
            'message_id' => $messageId !== '' ? $messageId : null,
            'in_reply_to' => $inReplyTo !== '' ? $inReplyTo : null,
            'subject' => $subject !== '' ? $subject : '(no subject)',
            'from_email' => $fromEmail !== '' ? $fromEmail : null,
            'from_name' => $fromName !== '' ? $fromName : null,
            'to' => $to !== '' ? $to : null,
            'cc' => $cc !== '' ? $cc : null,
            'participant_emails' => array_keys($participants),
            'sent_at' => $sentAt,
            'snippet' => $snippet !== '' ? $snippet : null,
            'body_text' => $text !== '' ? $text : null,
            'body_html' => $html !== '' ? $html : null,
            'has_attachments' => false,
            'raw_headers' => null,
            'is_read_local' => false,
        ]);

        return $record;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function firstAddress(mixed $addresses): array
    {
        $list = $this->addressList($addresses);
        if ($list === []) {
            return ['', ''];
        }

        $first = $list[0];

        return [(string) ($first['mail'] ?? ''), (string) ($first['personal'] ?? '')];
    }

    /**
     * @return list<array{mail: string, personal: string}>
     */
    private function addressList(mixed $addresses): array
    {
        if ($addresses === null) {
            return [];
        }

        $values = [];
        if ($addresses instanceof \Webklex\PHPIMAP\Attribute) {
            $values = $addresses->toArray();
        } elseif (is_iterable($addresses)) {
            foreach ($addresses as $item) {
                $values[] = $item;
            }
        } else {
            $values = [$addresses];
        }

        $out = [];
        foreach ($values as $item) {
            if ($item instanceof \Webklex\PHPIMAP\Address) {
                $out[] = ['mail' => (string) $item->mail, 'personal' => (string) $item->personal];
            } elseif (is_object($item) && isset($item->mail)) {
                $out[] = ['mail' => (string) $item->mail, 'personal' => (string) ($item->personal ?? '')];
            } elseif (is_string($item) && trim($item) !== '') {
                $out[] = ['mail' => trim($item), 'personal' => ''];
            }
        }

        return $out;
    }

    private function attrString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }
        if ($value instanceof \Webklex\PHPIMAP\Attribute) {
            return trim($value->toString());
        }
        if (is_object($value) && method_exists($value, 'toString')) {
            return trim((string) $value->toString());
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return trim((string) $value);
        }

        return trim((string) $value);
    }

    private function addressesToString(mixed $addresses): string
    {
        $parts = [];
        foreach ($this->addressList($addresses) as $item) {
            $mail = $item['mail'];
            $personal = trim($item['personal']);
            $parts[] = $personal !== '' ? "{$personal} <{$mail}>" : $mail;
        }

        return implode(', ', $parts);
    }
}
