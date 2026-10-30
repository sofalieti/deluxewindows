<div
    id="dw-omni-search"
    class="position-relative"
    style="width: min(22rem, 72vw)"
    data-admin-search="jump"
    data-endpoint="{{ route('platform.omni-search') }}"
>
    <div
        data-admin-search-ghost
        class="form-control border-0"
        aria-hidden="true"
        style="position: absolute; inset: 0; color: #9aa0a6; pointer-events: none; overflow: hidden; white-space: nowrap; background: #fff"
    ></div>
    <input
        id="dw-omni-search-input"
        data-admin-search-input
        type="search"
        class="form-control"
        style="position: relative; background: #fff"
        placeholder="Name, phone, or email"
        autocomplete="off"
        autocorrect="off"
        spellcheck="false"
        aria-label="Search the admin"
        aria-expanded="false"
        aria-autocomplete="both"
    >
</div>
