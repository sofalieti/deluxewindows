<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\Mailbox\GoogleMailboxOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Support\Facades\Toast;
use Throwable;

class GoogleMailboxOAuthController
{
    public function redirect(Request $request, GoogleMailboxOAuthService $oauth): RedirectResponse
    {
        abort_unless($request->user()?->hasAccess('platform.mailbox'), 403);

        try {
            return $oauth->redirect();
        } catch (Throwable $e) {
            Toast::error($e->getMessage());

            return redirect()->route('platform.mailbox.settings');
        }
    }

    public function callback(Request $request, GoogleMailboxOAuthService $oauth): RedirectResponse
    {
        abort_unless($request->user()?->hasAccess('platform.mailbox'), 403);

        if ($request->filled('error')) {
            Toast::error('Google authorization was cancelled or denied.');

            return redirect()->route('platform.mailbox.settings');
        }

        try {
            $setting = $oauth->handleCallback();
            Toast::success('Google mailbox connected as '.$setting->google_connected_email);
        } catch (Throwable $e) {
            Toast::error($e->getMessage());
        }

        return redirect()->route('platform.mailbox.settings');
    }
}
