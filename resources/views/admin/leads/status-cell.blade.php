@php
    /** @var \App\Models\Lead $lead */
    $color = $lead->statusColor();
    $statuses = \App\Models\Lead::STATUSES;
    $action = rtrim(url()->current(), '/').'/changeStatus';
@endphp

<form method="post" action="{{ $action }}" class="lead-status-form" data-original-status="{{ $lead->status }}">
    @csrf
    <input type="hidden" name="lead" value="{{ $lead->id }}">
    <select
        name="status"
        class="lead-status-select lead-status-select--{{ $color }}"
        aria-label="Lead status"
        onchange="window.leadStatusSelectChanged(this)"
    >
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button
        type="submit"
        class="lead-status-save"
        title="Save status"
        aria-label="Save status"
        hidden
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/>
        </svg>
    </button>
</form>
