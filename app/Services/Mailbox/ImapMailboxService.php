<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxAttachment;
use App\Models\MailboxMessage;
use App\Models\MailboxSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
    public function sync(?MailboxSetting $setting = null, int $maxSeconds = 150): array
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
            $offset = max(0, (int) ($cursors['email_offset'] ?? 0));
            $emailBudget = $maxSeconds < 30 ? 8 : 40;
            $slice = array_slice($emailList, $offset, $emailBudget);
            $foldersUsed = [];
            $candidates = 0;
            $connectedEmail = strtolower($this->oauth->accountEmail($setting));

            foreach ($this->foldersToSync($client, $setting) as $folderName) {
                try {
                    $folder = $client->getFolder($folderName);
                } catch (Throwable) {
                    $folder = null;
                }
                if ($folder === null) {
                    continue;
                }
                $foldersUsed[] = $folderName;

                $isSent = $this->looksLikeSentFolder($folderName);
                $messages = $this->fetchTargetedMessages($folder, $slice, $started, $maxSeconds);
                $candidates += $messages->count();

                foreach ($messages as $message) {
                    /** @var Message $message */
                    $result = $this->importMessage(
                        $message,
                        $folderName,
                        $clientEmails,
                        $isSent,
                        $connectedEmail,
                        true,
                    );
                    if ($result === 'imported') {
                        $imported++;
                    } else {
                        $skipped++;
                    }

                    if ((microtime(true) - $started) > $maxSeconds) {
                        $more = true;
                        break 2;
                    }
                }
            }

            $nextOffset = $offset + $emailBudget;
            if ($nextOffset < count($emailList)) {
                $cursors['email_offset'] = $nextOffset;
                $more = true;
            } else {
                $cursors['email_offset'] = 0;
            }

            $client->disconnect();

            $setting->folder_cursors = $cursors;
            $setting->last_sync_at = now();
            $message = 'Account '.$connectedEmail.' · folders '.implode(', ', $foldersUsed ?: ['none'])
                .'. Searched '.count($slice).' of '.count($emailList).' client emails, IMAP found '.$candidates
                .' · Synced: '.$imported.' new, '.$skipped.' skipped.';
            if ($emailList === []) {
                $message = 'No client emails found on leads/contacts. Add emails to cards, then sync again.';
            } elseif ($more) {
                $message .= ' More addresses still to search — run Sync again.';
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

        $this->ensureMessageBody($message);
        $this->storeMessage(
            $message,
            $folderName,
            $outbound ? MailboxMessage::DIRECTION_OUTBOUND : MailboxMessage::DIRECTION_INBOUND,
        );

        return 'imported';
    }

    /**
     * @param  list<string>  $emails
     */
    private function fetchTargetedMessages(mixed $folder, array $emails, float $started, int $maxSeconds): Collection
    {
        $found = collect();

        foreach (['local-services', 'localservices'] as $from) {
            $found = $found->merge($this->safeSearch($folder, fn ($query) => $query->whereFrom($from)->limit(40)));
        }
        $found = $found->merge($this->safeSearch($folder, fn ($query) => $query->whereSubject('Local Services')->limit(40)));

        foreach ($emails as $email) {
            if ((microtime(true) - $started) > $maxSeconds - 1) {
                break;
            }
            $raw = 'from:'.$email.' OR to:'.$email;
            $found = $found->merge($this->safeSearch($folder, fn ($query) => $query->where('CUSTOM X-GM-RAW '.$raw)->limit(50)));
            $found = $found->merge($this->safeSearch($folder, fn ($query) => $query->whereFrom($email)->limit(40)));
            $found = $found->merge($this->safeSearch($folder, fn ($query) => $query->whereTo($email)->limit(40)));
        }

        return $found->unique(fn (mixed $message): int => (int) $message->getUid())->values();
    }

    /**
     * @param  callable(\Webklex\PHPIMAP\Query\WhereQuery): mixed  $configure
     */
    private function safeSearch(mixed $folder, callable $configure): Collection
    {
        try {
            $query = $folder->messages()
                ->leaveUnread()
                ->setFetchBody(false);
            $configure($query);

            return collect($query->get());
        } catch (Throwable $e) {
            Log::warning('Mailbox IMAP search failed', ['error' => $e->getMessage()]);

            return collect();
        }
    }

    /**
     * @return list<string>
     */
    private function foldersToSync(Client $client, MailboxSetting $setting): array
    {
        $names = [trim((string) ($setting->folder ?: 'INBOX')) ?: 'INBOX'];

        try {
            foreach ($client->getFolders(false) as $folder) {
                $full = trim((string) ($folder->full_name ?? $folder->path ?? $folder->name ?? ''));
                if ($full === '') {
                    continue;
                }
                if ($this->looksLikeSentFolder($full) || $this->looksLikeAllMail($full)) {
                    $names[] = $full;
                }
            }
        } catch (Throwable $e) {
            Log::info('Mailbox could not list extra IMAP folders', ['error' => $e->getMessage()]);
        }

        foreach (['[Gmail]/All Mail', '[Gmail]/Sent Mail'] as $gmailFolder) {
            $names[] = $gmailFolder;
        }

        return array_values(array_unique($names));
    }

    private function looksLikeSentFolder(string $name): bool
    {
        $normalized = strtolower($name);

        return str_contains($normalized, 'sent')
            && ! str_contains($normalized, 'spam')
            && ! str_contains($normalized, 'trash');
    }

    private function looksLikeAllMail(string $name): bool
    {
        $normalized = strtolower($name);

        return str_contains($normalized, 'all mail');
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

        $attachments = $message->getAttachments();
        $hasAttachments = $attachments->count() > 0;

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
            'has_attachments' => $hasAttachments,
            'raw_headers' => null,
            'is_read_local' => false,
        ]);

        if ($hasAttachments) {
            foreach ($attachments as $attachment) {
                $filename = (string) ($attachment->getName() ?: 'attachment.bin');
                $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) ?: 'file';
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $relative = 'mailbox/'.$record->id.'/'.$safeName.($ext !== '' ? '.'.$ext : '');

                Storage::disk('local')->put($relative, $attachment->getContent());

                MailboxAttachment::query()->create([
                    'mailbox_message_id' => $record->id,
                    'filename' => $filename,
                    'mime' => (string) ($attachment->getMimeType() ?: 'application/octet-stream'),
                    'size' => (int) ($attachment->getSize() ?: 0),
                    'disk_path' => $relative,
                ]);
            }
        }

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
