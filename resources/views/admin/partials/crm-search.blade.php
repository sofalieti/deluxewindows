@php
    $pageParams = [
        'page', 'mine_page', 'spam_page', 'unprocessed_page', 'microsoft_page',
        'direct_page', 'google_page', 'seo_page', 'chatgpt_page', 'unhandled_page',
        'all_page', 'inbound_page', 'unreturned_page', 'outbound_page', 'other_page',
    ];
@endphp
<form method="get" action="{{ url()->current() }}" class="bg-white rounded shadow-sm p-3 mb-3">
    @foreach(request()->except(array_merge(['q'], $pageParams)) as $name => $value)
        @if(is_array($value))
            @foreach($value as $child => $childValue)
                @if(is_scalar($childValue))
                    <input type="hidden" name="{{ $name }}[{{ $child }}]" value="{{ $childValue }}">
                @endif
            @endforeach
        @elseif(is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <input
            type="search"
            name="q"
            value="{{ request('q') }}"
            class="form-control"
            style="max-width: 28rem"
            placeholder="Name, email, phone, or city"
            aria-label="Search"
        >
        <button type="submit" class="btn btn-primary">Search</button>
        @if(trim((string) request('q')) !== '')
            <a class="btn btn-link" href="{{ url()->current() }}">Clear</a>
        @endif
    </div>
</form>
