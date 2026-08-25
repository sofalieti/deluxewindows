@php
    /** @var \App\Models\PhoneClick $click */
    $color = $click->handlingStatusColor();
    $statuses = \App\Models\PhoneClick::HANDLING_STATUSES;
    $action = rtrim(url()->current(), '/').'/changeHandlingStatus';
@endphp

<div class="lead-status-form" data-original-status="{{ $click->handling_status }}">
    <select
        class="lead-status-select lead-status-select--{{ $color }}"
        aria-label="Handling status"
        data-handling-status-select
        onchange="this.className='lead-status-select lead-status-select--'+this.options[this.selectedIndex].dataset.color"
    >
        @foreach ($statuses as $value => $label)
            <option
                value="{{ $value }}"
                data-color="{{ (new \App\Models\PhoneClick(['handling_status' => $value]))->handlingStatusColor() }}"
                @selected($click->handling_status === $value)
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
        data-click-id="{{ $click->id }}"
        onclick="this.setAttribute('formaction', this.dataset.actionBase+'?click='+encodeURIComponent(this.dataset.clickId)+'&handling_status='+encodeURIComponent(this.parentElement.querySelector('[data-handling-status-select]').value))"
    >✓</button>
</div>
