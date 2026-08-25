<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Mailbox;

use App\Services\Mailbox\GoogleMailboxOAuthService;
use App\Services\Mailbox\ImapMailboxService;
use App\Services\Mailbox\MailboxSettingsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MailboxSettingsScreen extends Screen
{
    public function __construct(
        private readonly MailboxSettingsService $settings,
        private readonly ImapMailboxService $imap,
        private readonly GoogleMailboxOAuthService $oauth,
    ) {
    }

    public function query(): iterable
    {
        $setting = $this->settings->get();
        $connected = $this->oauth->isConnected($setting);

        return [
            'setting' => [
                'enabled' => (bool) $setting->enabled,
                'google_client_id' => $setting->google_client_id,
                'google_client_secret' => $setting->google_client_secret ? '••••••••' : '',
                'google_status' => $connected
                    ? ('Connected as '.($setting->google_connected_email ?: $setting->username))
                    : 'Not connected',
                'google_redirect_uri' => $this->oauth->redirectUri(),
                'imap_host' => $setting->imap_host,
                'imap_port' => $setting->imap_port,
                'imap_encryption' => $setting->imap_encryption,
                'smtp_host' => $setting->smtp_host,
                'smtp_port' => $setting->smtp_port,
                'smtp_encryption' => $setting->smtp_encryption,
                'username' => $setting->username,
                'password' => $setting->password ? '••••••••' : '',
                'folder' => $setting->folder,
                'subject_filter' => $setting->subject_filter,
                'from_filter' => $setting->from_filter,
                'import_rules' => 'Clients (leads/contacts) + Local Services by Google',
                'last_sync_at' => optional($setting->last_sync_at)->format('Y-m-d H:i:s') ?: '—',
                'last_error' => $setting->last_error ?: '—',
            ],
            'google_connected' => $connected,
        ];
    }

    public function name(): ?string
    {
        return 'Mailbox settings';
    }

    public function description(): ?string
    {
        return 'Connect Gmail via Google OAuth (no 2-Step / App Password). Sync never marks as read and never deletes on the server.';
    }

    public function permission(): ?iterable
    {
        return ['platform.mailbox'];
    }

    public function commandBar(): iterable
    {
        $setting = $this->settings->get();
        $bar = [
            Link::make('Inbox')
                ->icon('bs.inbox')
                ->route('platform.mailbox'),

            Button::make('Save')
                ->icon('bs.check-circle')
                ->method('save'),
        ];

        if ($this->oauth->isConnected($setting)) {
            $bar[] = Button::make('Disconnect Google')
                ->icon('bs.box-arrow-right')
                ->method('disconnectGoogle')
                ->confirm('Disconnect Google mailbox access?');
        } else {
            $bar[] = Link::make('Connect with Google')
                ->icon('bs.google')
                ->route('platform.mailbox.google.redirect')
                ->rawClick();
        }

        $bar[] = Button::make('Test connection')
            ->icon('bs.plug')
            ->method('testConnection');

        $bar[] = Button::make('Sync now')
            ->icon('bs.arrow-repeat')
            ->method('syncNow')
            ->novalidate()
            ->rawClick();

        return $bar;
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('setting.google_status')
                    ->title('Google connection')
                    ->readonly(),

                Input::make('setting.google_redirect_uri')
                    ->title('Authorized redirect URI')
                    ->readonly()
                    ->help('Paste this exact URI into Google Cloud → OAuth client → Authorized redirect URIs.'),

                Input::make('setting.google_client_id')
                    ->title('Google Client ID')
                    ->required()
                    ->help('From Google Cloud Console → APIs & Services → Credentials. Save this, then click Connect with Google.'),

                Input::make('setting.google_client_secret')
                    ->title('Google Client Secret')
                    ->type('password')
                    ->help('Leave as •••• to keep the current secret.'),
            ])->title('Google OAuth'),

            Layout::rows([
                CheckBox::make('setting.enabled')
                    ->title('Enable sync')
                    ->placeholder('Run scheduled IMAP sync')
                    ->sendTrueOrFalse(),

                Input::make('setting.import_rules')
                    ->title('What is imported')
                    ->readonly()
                    ->help('Inbox and Sent for all time: incoming and outgoing mail to lead/contact emails, plus every message from Local Services by Google. History imports in batches — press Sync now until it says it is finished. Nothing is deleted or marked read on Gmail.'),

                Input::make('setting.folder')
                    ->title('IMAP inbox folder')
                    ->value('INBOX'),
            ])->title('Sync rules'),

            Layout::rows([
                Input::make('setting.username')
                    ->title('Email (optional fallback)')
                    ->type('email')
                    ->help('Filled automatically after Connect with Google. App Password is optional fallback only.'),

                Input::make('setting.password')
                    ->title('App password (optional fallback)')
                    ->type('password')
                    ->help('Not needed when Google OAuth is connected.'),
            ])->title('Fallback password auth'),

            Layout::rows([
                Input::make('setting.imap_host')->title('IMAP host'),
                Input::make('setting.imap_port')->title('IMAP port')->type('number'),
                Select::make('setting.imap_encryption')
                    ->title('IMAP encryption')
                    ->options(['ssl' => 'SSL', 'tls' => 'TLS', 'false' => 'None']),
            ])->title('IMAP'),

            Layout::rows([
                Input::make('setting.smtp_host')->title('SMTP host'),
                Input::make('setting.smtp_port')->title('SMTP port')->type('number'),
                Select::make('setting.smtp_encryption')
                    ->title('SMTP encryption')
                    ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None']),
            ])->title('SMTP (send/reply)'),

            Layout::rows([
                Input::make('setting.last_sync_at')->title('Last sync')->readonly(),
                Input::make('setting.last_error')->title('Last error')->readonly(),
            ])->title('Status'),
        ];
    }

    public function save(Request $request): void
    {
        $data = $this->validatedSettings($request);
        $this->settings->update($data);
        Toast::info('Mailbox settings saved.');
    }

    public function disconnectGoogle(): void
    {
        $this->oauth->disconnect();
        Toast::info('Google mailbox disconnected.');
    }

    public function testConnection(Request $request): void
    {
        $this->settings->update($this->validatedSettings($request));
        $result = $this->imap->testConnection();
        if ($result['ok']) {
            Toast::success($result['message']);
        } else {
            Toast::error($result['message']);
        }
    }

    public function syncNow(Request $request)
    {
        $this->settings->update($this->validatedSettings($request));
        $result = $this->imap->sync(maxSeconds: 12);
        session()->flash('mailbox_sync_result', $result);

        if ($result['ok']) {
            Toast::success($result['message']);
        } else {
            Toast::error($result['message']);
        }

        return redirect()->route('platform.mailbox.settings');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedSettings(Request $request): array
    {
        $existing = $this->settings->get();

        $rules = [
            'setting.enabled' => ['sometimes', 'boolean'],
            'setting.google_client_id' => ['nullable', 'string', 'max:500'],
            'setting.google_client_secret' => ['nullable', 'string', 'max:500'],
            'setting.username' => ['nullable', 'email', 'max:255'],
            'setting.password' => ['nullable', 'string', 'max:255'],
            'setting.subject_filter' => ['nullable', 'string', 'max:255'],
            'setting.from_filter' => ['nullable', 'string', 'max:255'],
            'setting.folder' => ['nullable', 'string', 'max:255'],
            'setting.imap_host' => ['nullable', 'string', 'max:255'],
            'setting.imap_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'setting.imap_encryption' => ['nullable', 'string', 'max:16'],
            'setting.smtp_host' => ['nullable', 'string', 'max:255'],
            'setting.smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'setting.smtp_encryption' => ['nullable', 'string', 'max:16'],
        ];

        $data = $request->validate($rules)['setting'] ?? [];
        $data['auth_mode'] = 'oauth';

        unset(
            $data['google_status'],
            $data['google_redirect_uri'],
            $data['last_sync_at'],
            $data['last_error'],
            $data['import_rules'],
        );

        foreach (['google_client_id', 'google_client_secret', 'password'] as $secretField) {
            $value = trim((string) ($data[$secretField] ?? ''));
            if ($value === '' || $value === '••••••••') {
                unset($data[$secretField]);
            }
        }

        foreach (['imap_host', 'imap_port', 'imap_encryption', 'smtp_host', 'smtp_port', 'smtp_encryption', 'folder'] as $field) {
            if (! array_key_exists($field, $data) || $data[$field] === null || $data[$field] === '') {
                $data[$field] = $existing->{$field};
            }
        }

        return $data;
    }
}
