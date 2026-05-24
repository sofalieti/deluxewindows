@extends('webflow.layouts.app')

@section('title', '🔎 Search Results')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🔎 Search Results</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>search</code> | Path: <code>/search</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid">Search results</h1>' !!}</section>
    <section class="mb-3">{!! '<label for="search" class="hidden">Search</label>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid title"></h2>' !!}</section>
    <section class="mb-3">{!! '<div class="text-break-all"></div>' !!}</section>
    <section class="mb-3">{!! '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse varius enim in eros elementum tristique. Duis cursus, mi quis viverra ornare, eros dolor interdum nulla, ut commodo diam libero vitae erat. Aenean faucibus nibh et justo cursus id rutrum lorem imperdiet. Nunc ut sem vitae risus tristique posuere.</p>' !!}</section>
    <section class="mb-3">{!! '<div>No matching results.</div>' !!}</section>

    @if(!empty($items))
    <section class="mt-4">
        <h2 class="h5 mb-3">Collection items</h2>
        <div class="row g-3">
            @foreach($items as $item)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100">
                    <div class="card-body">
                        <h3 class="h6">{{ data_get($item, 'field_data.name', 'Untitled') }}</h3>
                        <pre class="small mb-0">{{ json_encode($item['field_data'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </article>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
