@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\MailboxMessage> $mailboxMessages */
    $mailboxMessages = $mailboxMessages ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h6 class="text-muted text-uppercase mb-0 contact-panel__title">Emails</h6>
        <span class="badge bg-primary text-white">{{ $mailboxMessages->count() }}</span>
    </div>

    @forelse ($mailboxMessages as $message)
        <div class="mailbox-client-row {{ $loop->last ? '' : 'mailbox-client-row--border' }}">
            <div class="mailbox-client-row__main">
                <div>
                    <a class="fw-semibold" href="{{ route('platform.mailbox.view', $message) }}">
                        {{ $message->subject ?: '(no subject)' }}
                    </a>
                    <div class="small text-muted mailbox-client-row__meta">
                        {{ $message->direction === \App\Models\MailboxMessage::DIRECTION_OUTBOUND ? 'Sent' : 'Received' }}
                        ·
                        {{ $message->direction === \App\Models\MailboxMessage::DIRECTION_OUTBOUND
                            ? 'To '.$message->to
                            : trim(($message->from_name ? $message->from_name.' ' : '').'<'.($message->from_email ?? '').'>') }}
                    </div>
                    @if ($message->snippet)
                        <div class="small mailbox-client-row__snippet">{{ \Illuminate\Support\Str::limit($message->snippet, 140) }}</div>
                    @endif
                </div>
                <span class="small text-muted mailbox-client-row__when">
                    {{ optional($message->sent_at ?? $message->created_at)->format('Y-m-d H:i') }}
                </span>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">No synced emails for this address yet. Run Mailbox → Sync now after the address is saved.</p>
    @endforelse
</div>
