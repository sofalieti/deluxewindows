<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxAttachment;
use App\Models\MailboxMessage;
use App\Models\MailboxSetting;
use Carbon\Carbon;
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
    public function sync(?MailboxSetting $setting = null): array
    {
        $setting ??= $this->settings->get();

        if (! $setting->enabled) {
            return ['ok' => false, 'imported' => 0, 'skipped' => 0, 'message' => 'Mailbox sync is disabled.'];
        }

        if (! $this->hasCredentials($setting)) {
            return ['ok' => false, 'imported' => 0, 'skipped' => 0, 'message' => 'Connect Google mailbox or configure IMAP credentials first.'];
        }

        $imported = 0;
        $skipped = 0;

        try {
            $client = $this->connect($setting);
            $folderName = $setting->folder ?: 'INBOX';
            $folder = $client->getFolder($folderName);
            if ($folder === null) {
                throw new \RuntimeException('Folder not found: '.$folderName);
            }

            $subjectFilter = trim((string) ($setting->subject_filter ?: 'Deluxewindows'));
            $fromFilter = trim((string) ($setting->from_filter ?: 'notify.deluxewindows.com'));
            // IMAP SINCE is inclusive by calendar day — start from yesterday.
            $since = Carbon::now()->subDay()->startOfDay();

            // leaveUnread / FT_PEEK: fetch without setting \Seen
            $query = $folder->messages()
                ->leaveUnread()
                ->setFetchBody(true)
                ->setFetchFlags(false)
                ->whereSince($since);

            if ($subjectFilter !== '') {
                $query->whereSubject($subjectFilter);
            }

            if ($fromFilter !== '') {
                $query->whereFrom($fromFilter);
            }

            $messages = $query->get();

            foreach ($messages as $message) {
                /** @var Message $message */
                $uid = (int) $message->getUid();
                if ($uid <= 0) {
                    $skipped++;
                    continue;
                }

                $exists = MailboxMessage::query()
                    ->where('folder', $folderName)
                    ->where('imap_uid', $uid)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $subject = $this->attrString($message->getSubject());
                if ($subjectFilter !== '' && stripos($subject, $subjectFilter) === false) {
                    $skipped++;
                    continue;
                }

                [$fromEmail] = $this->firstAddress($message->getFrom());
                if ($fromFilter !== '' && stripos($fromEmail, $fromFilter) === false) {
                    $skipped++;
                    continue;
                }

                try {
                    $dateAttr = $message->getDate();
                    if ($dateAttr instanceof \Webklex\PHPIMAP\Attribute) {
                        $messageDate = $dateAttr->toDate();
                        if ($messageDate->lt($since)) {
                            $skipped++;
                            continue;
                        }
                    }
                } catch (Throwable) {
                    // If date cannot be parsed, still keep the message when IMAP SINCE matched.
                }

                $this->storeMessage($message, $folderName);
                $imported++;
            }

            $client->disconnect();

            $setting->last_sync_at = now();
            $setting->last_error = null;
            $setting->save();
            $this->settings->forgetCache();

            return [
                'ok' => true,
                'imported' => $imported,
                'skipped' => $skipped,
                'message' => "Synced: {$imported} new, {$skipped} skipped.",
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

    private function storeMessage(Message $message, string $folderName): MailboxMessage
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

        $record = MailboxMessage::query()->create([
            'direction' => MailboxMessage::DIRECTION_INBOUND,
            'folder' => $folderName,
            'imap_uid' => (int) $message->getUid(),
            'message_id' => $messageId !== '' ? $messageId : null,
            'in_reply_to' => $inReplyTo !== '' ? $inReplyTo : null,
            'subject' => $subject !== '' ? $subject : '(no subject)',
            'from_email' => $fromEmail !== '' ? $fromEmail : null,
            'from_name' => $fromName !== '' ? $fromName : null,
            'to' => $to !== '' ? $to : null,
            'cc' => $cc !== '' ? $cc : null,
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
