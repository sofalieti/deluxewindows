@php
    /** @var \App\Models\RingCentralCall $call */
    $color = $call->handlingStatusColor();
    $statuses = \App\Models\RingCentralCall::HANDLING_STATUSES;
    $action = rtrim(url()->current(), '/').'/changeHandlingStatus';
@endphp

<div class="lead-status-form" data-original-status="{{ $call->handling_status }}">
    <select
        class="lead-status-select lead-status-select--{{ $color }}"
        aria-label="Handling status"
        data-handling-status-select
        onchange="this.className='lead-status-select lead-status-select--'+this.options[this.selectedIndex].dataset.color"
    >
        @foreach ($statuses as $value => $label)
            <option
                value="{{ $value }}"
                data-color="{{ (new \App\Models\RingCentralCall(['handling_status' => $value]))->handlingStatusColor() }}"
                @selected($call->handling_status === $value)
            >{{ $label }}</option>
        @endforeach
    </select>
    <button
        type="submit"
        form="post-form"
        class="lead-status-save"
        title="Save handling status"
        aria-label="Save handling status"
        data-action-base="{{ $action }}"
        data-call-id="{{ $call->id }}"
        onclick="this.setAttribute('formaction', this.dataset.actionBase+'?call='+encodeURIComponent(this.dataset.callId)+'&handling_status='+encodeURIComponent(this.parentElement.querySelector('[data-handling-status-select]').value))"
    >✓</button>
</div>
