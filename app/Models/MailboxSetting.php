<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailboxSetting extends Model
{
    protected $fillable = [
        'scope',
        'enabled',
        'auth_mode',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'username',
        'password',
        'google_client_id',
        'google_client_secret',
        'google_refresh_token',
        'google_access_token',
        'google_token_expires_at',
        'google_connected_email',
        'folder',
        'subject_filter',
        'from_filter',
        'last_sync_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'imap_port' => 'integer',
            'smtp_port' => 'integer',
            'password' => 'encrypted',
            'google_client_secret' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_access_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
            'last_sync_at' => 'datetime',
        ];
    }

    public function usesOAuth(): bool
    {
        return ($this->auth_mode ?: 'oauth') === 'oauth'
            && trim((string) $this->google_refresh_token) !== '';
    }
}
