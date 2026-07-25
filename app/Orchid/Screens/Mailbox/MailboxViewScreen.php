<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Mailbox;

use App\Models\MailboxAttachment;
use App\Models\MailboxMessage;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Support\Facades\Layout;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailboxViewScreen extends Screen
{
    public ?MailboxMessage $message = null;

    public function query(MailboxMessage $message): iterable
    {
        $message->load('attachments');
        $message->is_read_local = true;
        $message->save();

        $this->message = $message;

        return [
            'message' => $message,
            'body_html' => HtmlSanitizer::mailboxBody($message->body_html),
            'body_text' => (string) $message->body_text,
        ];
    }

    public function name(): ?string
    {
        return $this->message
            ? (string) ($this->message->subject ?: '(no subject)')
            : 'Message';
    }

    public function description(): ?string
    {
        if (! $this->message) {
            return null;
        }

        $from = trim((string) ($this->message->from_name ?: $this->message->from_email));

        return ($this->message->direction === MailboxMessage::DIRECTION_OUTBOUND ? 'Sent' : 'From '.$from)
            .' · '.optional($this->message->sent_at)->format('Y-m-d H:i');
    }

    public function permission(): ?iterable
    {
        return ['platform.mailbox'];
    }

    public function commandBar(): iterable
    {
        $items = [
            Link::make('Back')
                ->icon('bs.arrow-left')
                ->route('platform.mailbox'),
        ];

        if ($this->message && $this->message->direction === MailboxMessage::DIRECTION_INBOUND) {
            $items[] = Link::make('Reply')
                ->icon('bs.reply')
                ->route('platform.mailbox.compose', ['reply' => $this->message->id]);
        }

        return $items;
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('message', [
                Sight::make('direction', 'Direction'),
                Sight::make('from_email', 'From')->render(fn (MailboxMessage $m) => e(trim(($m->from_name ? $m->from_name.' ' : '').'<'.($m->from_email ?? '').'>'))),
                Sight::make('to', 'To'),
                Sight::make('cc', 'Cc'),
                Sight::make('sent_at', 'Date')->render(fn (MailboxMessage $m) => optional($m->sent_at)->format('Y-m-d H:i:s') ?: '—'),
                Sight::make('message_id', 'Message-ID'),
            ]),

            Layout::view('admin.mailbox.message-body'),

            Layout::view('admin.mailbox.attachments'),
        ];
    }

    public function downloadAttachment(Request $request): StreamedResponse
    {
        $id = (int) ($request->input('attachment') ?: $request->query('attachment'));
        $attachment = MailboxAttachment::query()->findOrFail($id);

        abort_unless(Storage::disk('local')->exists($attachment->disk_path), 404);

        return Storage::disk('local')->download($attachment->disk_path, $attachment->filename);
    }
}
