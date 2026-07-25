@php
    /** @var \App\Models\Lead $lead */
    $color = $lead->statusColor();
    $statuses = \App\Models\Lead::STATUSES;
    $action = rtrim(url()->current(), '/').'/changeStatus';
@endphp

<form method="post" action="{{ $action }}" class="lead-status-form">
    @csrf
    <input type="hidden" name="lead" value="{{ $lead->id }}">
    <select
        name="status"
        class="lead-status-select lead-status-select--{{ $color }}"
        aria-label="Lead status"
        onchange="this.form.requestSubmit()"
    >
        @foreach ($statuses as $value => $label)
            <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
        @endforeach
    </select>
</form>
