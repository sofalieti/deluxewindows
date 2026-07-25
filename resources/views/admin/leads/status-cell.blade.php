@php
    /** @var \App\Models\Lead $lead */
    $color = $lead->statusColor();
    $statuses = \App\Models\Lead::STATUSES;
    $action = rtrim(url()->current(), '/').'/changeStatus';
@endphp

{{-- Outside nested <form>: Orchid wraps the screen in #post-form --}}
<div class="lead-status-form" data-original-status="{{ $lead->status }}">
    <select
        class="lead-status-select lead-status-select--{{ $color }}"
        aria-label="Lead status"
        data-lead-status-select
        onchange="this.className='lead-status-select lead-status-select--'+this.value"
    >
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button
        type="submit"
        form="post-form"
        class="lead-status-save"
        title="Save status"
        aria-label="Save status"
        data-action-base="{{ $action }}"
        data-lead-id="{{ $lead->id }}"
        onclick="this.setAttribute('formaction', this.dataset.actionBase+'?lead='+encodeURIComponent(this.dataset.leadId)+'&status='+encodeURIComponent(this.parentElement.querySelector('[data-lead-status-select]').value))"
    >✓</button>
</div>
