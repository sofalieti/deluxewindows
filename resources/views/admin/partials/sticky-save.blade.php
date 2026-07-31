@php
    $label = $label ?? 'Save';
    $method = $method ?? 'save';
    $action = rtrim(url()->current(), '/').'/'.$method;
@endphp

<div class="admin-sticky-actions">
    <button
        type="submit"
        form="post-form"
        formaction="{{ $action }}"
        class="btn btn-primary admin-sticky-actions__save"
    >
        {{ $label }}
    </button>
</div>
