@php
    $action = rtrim(url()->current(), '/').'/addComment';
@endphp

<div class="admin-comment-actions">
    <button
        type="submit"
        form="post-form"
        formaction="{{ $action }}"
        class="btn btn-primary"
    >
        Add comment
    </button>
</div>
