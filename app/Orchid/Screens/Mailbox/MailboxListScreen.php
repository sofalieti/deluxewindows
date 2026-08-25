<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Mailbox;

use App\Models\MailboxMessage;
use App\Services\Mailbox\ImapMailboxService;
use App\Services\Mailbox\MailboxSettingsService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MailboxListScreen extends Screen
{
    public function query(): iterable
    {
        $q = trim((string) request()->input('filter.q', ''));

        $messages = MailboxMessage::query()
            ->defaultSort('sent_at', 'desc')
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($inner) use ($q): void {
                    $inner->where('subject', 'like', '%'.$q.'%')
                        ->orWhere('from_email', 'like', '%'.$q.'%')
                        ->orWhere('from_name', 'like', '%'.$q.'%')
                        ->orWhere('snippet', 'like', '%'.$q.'%')
                        ->orWhere('to', 'like', '%'.$q.'%');
                });
            });

        $setting = app(MailboxSettingsService::class)->get();

        return [
            'messages' => $messages->paginate(50),
            'filter' => ['q' => $q],
            'setting' => $setting,
            'clientEmailCount' => count(app(\App\Services\Mailbox\MailboxClientEmailDirectory::class)->normalizedSet()),
        ];
    }

    public function name(): ?string
    {
        return 'Mailbox';
    }

    public function description(): ?string
    {
        return 'Incoming and outgoing mail for CRM client addresses, plus Local Services by Google. Full history, never deleted on the server.';
    }

    public function permission(): ?iterable
    {
        return ['platform.mailbox'];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Search')
                ->icon('bs.search')
                ->method('search'),

            Link::make('Compose')
                ->icon('bs.pencil-square')
                ->route('platform.mailbox.compose'),

            Link::make('Settings')
                ->icon('bs.gear')
                ->route('platform.mailbox.settings'),

            Button::make('Sync now')
                ->icon('bs.arrow-repeat')
                ->method('syncNow')
                ->novalidate()
                ->rawClick(),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::view('admin.mailbox.sync-status'),
            Layout::rows([
                Input::make('filter.q')
                    ->title('Search')
                    ->placeholder('Subject, from, snippet…'),
            ]),

            Layout::table('messages', [
                TD::make('direction', '')
                    ->width('40px')
                    ->render(fn (MailboxMessage $m) => $m->direction === MailboxMessage::DIRECTION_OUTBOUND
                        ? '↑'
                        : '↓'),

                TD::make('sent_at', 'Date')
                    ->sort()
                    ->render(fn (MailboxMessage $m) => optional($m->sent_at ?? $m->created_at)->format('Y-m-d H:i')),

                TD::make('from_email', 'From / To')
                    ->render(function (MailboxMessage $m): string {
                        if ($m->direction === MailboxMessage::DIRECTION_OUTBOUND) {
                            return 'To: '.e((string) $m->to);
                        }
                        $name = trim((string) $m->from_name);
                        $email = trim((string) $m->from_email);

                        return e($name !== '' ? "{$name} <{$email}>" : $email);
                    }),

                TD::make('subject', 'Subject')
                    ->render(fn (MailboxMessage $m) => Link::make((string) ($m->subject ?: '(no subject)'))
                        ->route('platform.mailbox.view', $m)),

                TD::make('snippet', 'Preview')
                    ->render(fn (MailboxMessage $m) => e(\Illuminate\Support\Str::limit((string) $m->snippet, 80))),
            ]),
        ];
    }

    public function syncNow(ImapMailboxService $imap)
    {
        $result = $imap->sync(maxSeconds: 12);
        session()->flash('mailbox_sync_result', $result);

        if ($result['ok']) {
            Toast::success($result['message']);
        } else {
            Toast::error($result['message']);
        }

        return redirect()->route('platform.mailbox');
    }

    public function search(Request $request)
    {
        return redirect()->route('platform.mailbox', [
            'filter' => [
                'q' => trim((string) $request->input('filter.q', '')),
            ],
        ]);
    }
}
