@php
    /** @var \App\Models\MailboxSetting|null $setting */
    $setting = $setting ?? null;
@endphp

@if ($setting)
    <div class="bg-white rounded shadow-sm p-3 mb-3">
        <div class="small text-muted">
            Last sync: {{ optional($setting->last_sync_at)->format('Y-m-d H:i:s') ?: 'never' }}
            @if (filled($setting->last_error) && $setting->last_error !== '—')
                · Last error: {{ $setting->last_error }}
            @endif
        </div>
    </div>
@endif
