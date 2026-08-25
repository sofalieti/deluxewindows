@php
    /** @var \App\Models\MailboxSetting|null $setting */
    $setting = $setting ?? null;
    $flash = session('mailbox_sync_result');
    $lastSync = $setting?->last_sync_at;
@endphp

<div class="bg-white rounded shadow-sm p-3 mb-3">
    @if (is_array($flash))
        <div class="{{ ! empty($flash['ok']) ? 'text-success' : 'text-danger' }} fw-semibold mb-2">
            {{ $flash['message'] ?? 'Sync finished.' }}
        </div>
    @endif

    @if ($setting)
        <div class="small text-muted">
            Last sync:
            @if ($lastSync)
                {{ $lastSync->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                ({{ $lastSync->diffForHumans() }})
            @else
                never
            @endif
            · Messages in mailbox: {{ \App\Models\MailboxMessage::query()->count() }}
            · Client emails in CRM: {{ $clientEmailCount ?? 0 }}
            @if (filled($setting->last_error))
                <div class="text-danger mt-1">{{ $setting->last_error }}</div>
            @endif
        </div>
    @endif
</div>
