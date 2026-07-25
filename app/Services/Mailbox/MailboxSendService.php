<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxMessage;
use App\Models\MailboxSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Throwable;

class MailboxSendService
{
    public function __construct(
        private readonly MailboxSettingsService $settings,
        private readonly GoogleMailboxOAuthService $oauth,
    ) {
    }

    /**
     * @return array{ok: bool, message: string, record?: MailboxMessage}
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $inReplyTo = null,
        bool $isHtml = false,
    ): array {
        $setting = $this->settings->get();
        $from = $this->oauth->accountEmail($setting);

        if ($from === '') {
            return ['ok' => false, 'message' => 'Mailbox email is not configured. Connect Google first.'];
        }

        if (! $this->oauth->isConnected($setting) && trim((string) $setting->password) === '') {
            return ['ok' => false, 'message' => 'Connect Google mailbox or set an App Password first.'];
        }

        $to = trim($to);
        $subject = trim($subject);
        $body = trim($body);

        if ($to === '' || $subject === '' || $body === '') {
            return ['ok' => false, 'message' => 'To, subject and body are required.'];
        }

        $messageId = sprintf('%s@%s', Str::uuid()->toString(), $this->domainFromEmail($from));

        try {
            $this->withSmtpConfig($setting, $from, function () use ($from, $to, $subject, $body, $isHtml, $inReplyTo, $messageId): void {
                Mail::mailer('mailbox')->send([], [], function ($message) use ($from, $to, $subject, $body, $isHtml, $inReplyTo, $messageId): void {
                    /** @var \Illuminate\Mail\Message $message */
                    $message->from($from)->to($to)->subject($subject);

                    if ($isHtml) {
                        $message->html($body);
                    } else {
                        $message->text($body);
                        $message->html(nl2br(e($body)));
                    }

                    $symfony = $message->getSymfonyMessage();
                    if ($symfony instanceof Email) {
                        $headers = $symfony->getHeaders();
                        $headers->addIdHeader('Message-ID', $messageId);
                        if ($inReplyTo !== null && trim($inReplyTo) !== '') {
                            $headers->addTextHeader('In-Reply-To', trim($inReplyTo));
                            $headers->addTextHeader('References', trim($inReplyTo));
                        }
                    }
                });
            });

            $storedId = '<'.$messageId.'>';

            $record = MailboxMessage::query()->create([
                'direction' => MailboxMessage::DIRECTION_OUTBOUND,
                'folder' => 'SENT',
                'imap_uid' => null,
                'message_id' => $storedId,
                'in_reply_to' => $inReplyTo,
                'subject' => $subject,
                'from_email' => $from,
                'from_name' => null,
                'to' => $to,
                'cc' => null,
                'sent_at' => now(),
                'snippet' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? ''), 480),
                'body_text' => $isHtml ? strip_tags($body) : $body,
                'body_html' => $isHtml ? $body : nl2br(e($body)),
                'has_attachments' => false,
                'is_read_local' => true,
            ]);

            return ['ok' => true, 'message' => 'Message sent.', 'record' => $record];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param  callable(): void  $callback
     */
    private function withSmtpConfig(MailboxSetting $setting, string $from, callable $callback): void
    {
        $encryption = $setting->smtp_encryption ?: 'tls';
        if ($encryption === 'none' || $encryption === 'false') {
            $encryption = null;
        }

        $host = $setting->smtp_host ?: 'smtp.gmail.com';
        $port = (int) ($setting->smtp_port ?: 587);

        if ($this->oauth->isConnected($setting)) {
            $token = $this->oauth->getValidAccessToken($setting);
            $encQuery = $encryption ? '&encryption='.rawurlencode($encryption) : '';
            $url = sprintf(
                'smtp://%s:%s@%s:%d?auth_mode=XOAUTH2%s',
                rawurlencode($from),
                rawurlencode($token),
                $host,
                $port,
                $encQuery
            );

            config([
                'mail.mailers.mailbox' => [
                    'transport' => 'smtp',
                    'url' => $url,
                    'timeout' => 30,
                ],
                'mail.from.address' => $from,
                'mail.from.name' => 'Deluxe Windows',
            ]);
        } else {
            config([
                'mail.mailers.mailbox' => [
                    'transport' => 'smtp',
                    'host' => $host,
                    'port' => $port,
                    'encryption' => $encryption,
                    'username' => $from,
                    'password' => (string) $setting->password,
                    'timeout' => 30,
                ],
                'mail.from.address' => $from,
                'mail.from.name' => 'Deluxe Windows',
            ]);
        }

        $callback();
    }

    private function domainFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        $domain = $parts[1] ?? 'localhost';

        return $domain !== '' ? $domain : 'localhost';
    }
}
