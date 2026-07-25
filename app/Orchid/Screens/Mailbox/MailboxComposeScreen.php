<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Mailbox;

use App\Models\MailboxMessage;
use App\Services\Mailbox\MailboxSendService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MailboxComposeScreen extends Screen
{
    public ?MailboxMessage $replyTo = null;

    public function __construct(
        private readonly MailboxSendService $sendService,
    ) {
    }

    public function query(): iterable
    {
        $replyId = (int) request()->input('reply', 0);
        $to = '';
        $subject = '';
        $body = '';
        $inReplyTo = '';

        if ($replyId > 0) {
            $this->replyTo = MailboxMessage::query()->find($replyId);
            if ($this->replyTo) {
                $to = (string) $this->replyTo->from_email;
                $origSubject = (string) $this->replyTo->subject;
                $subject = str_starts_with(strtolower($origSubject), 're:')
                    ? $origSubject
                    : 'Re: '.$origSubject;
                $quoted = trim((string) ($this->replyTo->body_text ?: strip_tags((string) $this->replyTo->body_html)));
                $body = "\n\n\n--- Original message ---\n".$quoted;
                $inReplyTo = (string) ($this->replyTo->message_id ?? '');
            }
        }

        return [
            'compose' => [
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'in_reply_to' => $inReplyTo,
            ],
        ];
    }

    public function name(): ?string
    {
        return $this->replyTo ? 'Reply' : 'Compose';
    }

    public function description(): ?string
    {
        return 'Send via SMTP using the mailbox App Password from Settings.';
    }

    public function permission(): ?iterable
    {
        return ['platform.mailbox'];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Back')
                ->icon('bs.arrow-left')
                ->route('platform.mailbox'),

            Button::make('Send')
                ->icon('bs.send')
                ->method('send'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('compose.to')
                    ->title('To')
                    ->type('email')
                    ->required(),

                Input::make('compose.subject')
                    ->title('Subject')
                    ->required(),

                TextArea::make('compose.body')
                    ->title('Message')
                    ->rows(16)
                    ->required(),

                Input::make('compose.in_reply_to')
                    ->type('hidden'),
            ]),
        ];
    }

    public function send(Request $request)
    {
        $data = $request->validate([
            'compose.to' => ['required', 'email', 'max:255'],
            'compose.subject' => ['required', 'string', 'max:500'],
            'compose.body' => ['required', 'string', 'max:100000'],
            'compose.in_reply_to' => ['nullable', 'string', 'max:500'],
        ])['compose'];

        $result = $this->sendService->send(
            $data['to'],
            $data['subject'],
            $data['body'],
            $data['in_reply_to'] ?: null,
            false,
        );

        if (! $result['ok']) {
            Toast::error($result['message']);

            return;
        }

        Toast::success($result['message']);

        /** @var MailboxMessage $record */
        $record = $result['record'];

        return redirect()->route('platform.mailbox.view', $record);
    }
}
