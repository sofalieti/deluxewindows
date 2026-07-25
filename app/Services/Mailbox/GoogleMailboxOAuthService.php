<?php

declare(strict_types=1);

namespace App\Services\Mailbox;

use App\Models\MailboxSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Throwable;

class GoogleMailboxOAuthService
{
    public const SCOPE_MAIL = 'https://mail.google.com/';

    public function __construct(
        private readonly MailboxSettingsService $settings,
    ) {
    }

    public function isConnected(?MailboxSetting $setting = null): bool
    {
        $setting ??= $this->settings->get();

        return trim((string) $setting->google_refresh_token) !== ''
            && trim((string) ($setting->google_connected_email ?: $setting->username)) !== '';
    }

    public function hasOAuthAppConfigured(?MailboxSetting $setting = null): bool
    {
        $setting ??= $this->settings->get();

        return trim((string) $setting->google_client_id) !== ''
            && trim((string) $setting->google_client_secret) !== '';
    }

    public function redirect(): RedirectResponse
    {
        $setting = $this->settings->get();
        if (! $this->hasOAuthAppConfigured($setting)) {
            throw new \RuntimeException('Save Google Client ID and Client Secret first.');
        }

        return $this->driver($setting)
            ->scopes([self::SCOPE_MAIL, 'email', 'profile', 'openid'])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function handleCallback(): MailboxSetting
    {
        $setting = $this->settings->get();
        if (! $this->hasOAuthAppConfigured($setting)) {
            throw new \RuntimeException('Google OAuth app is not configured.');
        }

        $googleUser = $this->driver($setting)->user();
        $email = trim((string) ($googleUser->getEmail() ?? ''));
        if ($email === '') {
            throw new \RuntimeException('Google did not return an email address.');
        }

        $refresh = (string) ($googleUser->refreshToken ?? '');
        $access = (string) ($googleUser->token ?? '');
        $expiresIn = (int) ($googleUser->expiresIn ?? 3600);

        if ($access === '') {
            throw new \RuntimeException('Google did not return an access token.');
        }

        $data = [
            'auth_mode' => 'oauth',
            'username' => $email,
            'google_connected_email' => $email,
            'google_access_token' => $access,
            'google_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
            'last_error' => null,
        ];

        // Google only returns refresh_token on first consent / prompt=consent.
        if ($refresh !== '') {
            $data['google_refresh_token'] = $refresh;
        } elseif (trim((string) $setting->google_refresh_token) === '') {
            throw new \RuntimeException('No refresh token received. Disconnect the app in Google Account → Security → Third-party access, then Connect again.');
        }

        return $this->settings->update($data);
    }

    public function disconnect(): MailboxSetting
    {
        return $this->settings->update([
            'auth_mode' => 'oauth',
            'google_refresh_token' => null,
            'google_access_token' => null,
            'google_token_expires_at' => null,
            'google_connected_email' => null,
        ]);
    }

    /**
     * Fresh access token for IMAP/SMTP XOAUTH2.
     */
    public function getValidAccessToken(?MailboxSetting $setting = null): string
    {
        $setting ??= $this->settings->get();

        if (! $this->isConnected($setting)) {
            throw new \RuntimeException('Google mailbox is not connected. Use Connect with Google in settings.');
        }

        $expiresAt = $setting->google_token_expires_at;
        $access = trim((string) $setting->google_access_token);
        if ($access !== '' && $expiresAt instanceof Carbon && $expiresAt->isFuture()) {
            return $access;
        }

        return $this->refreshAccessToken($setting);
    }

    public function accountEmail(?MailboxSetting $setting = null): string
    {
        $setting ??= $this->settings->get();

        return trim((string) ($setting->google_connected_email ?: $setting->username));
    }

    private function refreshAccessToken(MailboxSetting $setting): string
    {
        $refresh = trim((string) $setting->google_refresh_token);
        if ($refresh === '') {
            throw new \RuntimeException('Missing Google refresh token. Connect with Google again.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => (string) $setting->google_client_id,
            'client_secret' => (string) $setting->google_client_secret,
            'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            Log::warning('Google mailbox token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException('Failed to refresh Google access token. Re-connect the mailbox.');
        }

        $access = trim((string) $response->json('access_token'));
        $expiresIn = (int) ($response->json('expires_in') ?? 3600);
        if ($access === '') {
            throw new \RuntimeException('Google token refresh returned an empty access token.');
        }

        $this->settings->update([
            'google_access_token' => $access,
            'google_token_expires_at' => now()->addSeconds(max(60, $expiresIn - 60)),
            'last_error' => null,
        ]);

        return $access;
    }

    private function driver(MailboxSetting $setting): GoogleProvider
    {
        $this->configureServices($setting);

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver;
    }

    public function configureServices(?MailboxSetting $setting = null): void
    {
        $setting ??= $this->settings->get();

        config([
            'services.google.client_id' => (string) $setting->google_client_id,
            'services.google.client_secret' => (string) $setting->google_client_secret,
            'services.google.redirect' => route('platform.mailbox.google.callback'),
        ]);
    }

    public function redirectUri(): string
    {
        try {
            return route('platform.mailbox.google.callback');
        } catch (Throwable) {
            return url('/admin/mailbox/google/callback');
        }
    }
}
