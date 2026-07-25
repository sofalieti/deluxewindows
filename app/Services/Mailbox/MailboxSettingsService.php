<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MailboxSettingsService
{
    private const CACHE_KEY = 'mailbox.settings.default';

    public function get(): MailboxSetting
    {
        if (! Schema::hasTable('mailbox_settings')) {
            return new MailboxSetting([
                'scope' => 'default',
                'enabled' => false,
                'auth_mode' => 'oauth',
                'imap_host' => 'imap.gmail.com',
                'imap_port' => 993,
                'imap_encryption' => 'ssl',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_port' => 587,
                'smtp_encryption' => 'tls',
                'folder' => 'INBOX',
                'subject_filter' => 'Deluxewindows',
                'from_filter' => 'notify.deluxewindows.com',
            ]);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHour(), function () {
            return MailboxSetting::query()->firstOrCreate(
                ['scope' => 'default'],
                [
                    'enabled' => false,
                    'auth_mode' => 'oauth',
                    'imap_host' => 'imap.gmail.com',
                    'imap_port' => 993,
                    'imap_encryption' => 'ssl',
                    'smtp_host' => 'smtp.gmail.com',
                    'smtp_port' => 587,
                    'smtp_encryption' => 'tls',
                    'folder' => 'INBOX',
                    'subject_filter' => 'Deluxewindows',
                    'from_filter' => 'notify.deluxewindows.com',
                ]
            );
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): MailboxSetting
    {
        $setting = $this->get();

        foreach (['password', 'google_client_secret'] as $secretField) {
            if (array_key_exists($secretField, $data)) {
                $value = trim((string) ($data[$secretField] ?? ''));
                if ($value === '' || $value === '••••••••') {
                    unset($data[$secretField]);
                } else {
                    $data[$secretField] = $value;
                }
            }
        }

        // Allow explicitly clearing OAuth tokens via null.
        foreach (['google_refresh_token', 'google_access_token', 'google_token_expires_at', 'google_connected_email'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $setting->fill($data);
        $setting->save();
        $this->forgetCache();

        return $setting->fresh() ?? $setting;
    }
}
