@php
    /** @var \App\Models\Lead $lead */
    /** @var \Illuminate\Support\Collection<int|string, string> $assignees */
    $color = $lead->statusColor();
    $statuses = \App\Models\Lead::STATUSES;
    $statusAction = rtrim(url()->current(), '/').'/changeStatus';
    $assigneeAction = rtrim(url()->current(), '/').'/changeAssignee';
@endphp

{{-- Outside nested <form>: Orchid wraps the screen in #post-form --}}
<div class="lead-status-stack">
    <div class="lead-status-form" data-original-status="{{ $lead->status }}">
        <select
            class="lead-status-select lead-status-select--{{ $color }}"
            aria-label="Lead status"
            data-lead-inline-select
            data-action-base="{{ $statusAction }}"
            data-lead-id="{{ $lead->id }}"
            data-param="status"
            onchange="this.className='lead-status-select lead-status-select--'+this.value; window.leadInlineSubmit && window.leadInlineSubmit(this)"
        >
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($lead->status === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="lead-assignee-form">
        <select
            class="lead-assignee-select"
            aria-label="Assignee"
            data-lead-inline-select
            data-action-base="{{ $assigneeAction }}"
            data-lead-id="{{ $lead->id }}"
            data-param="assigned_to"
            onchange="window.leadInlineSubmit && window.leadInlineSubmit(this)"
        >
            <option value="" @selected($lead->assigned_to === null)>Unassigned</option>
            @foreach ($assignees as $id => $name)
                <option value="{{ $id }}" @selected((int) $lead->assigned_to === (int) $id)>{{ $name }}</option>
            @endforeach
        </select>
    </div>
</div>
