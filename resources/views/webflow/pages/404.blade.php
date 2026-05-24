@extends('webflow.layouts.app')

@section('title', '🔎 404 Not Found')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🔎 404 Not Found</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>404</code> | Path: <code>/404</code></p>

    <section class="mb-3">{!! '<div class="_404-number">404</div>' !!}</section>
    <section class="mb-3">{!! '<h1 class="display-8 mid">Oops...page <span class="text-no-wrap" data-w-id="1376c7d4-75a8-498c-cbe5-246c32d5a304">not found</span></h1>' !!}</section>
    <section class="mb-3">{!! '<p>Lorem ipsum dolor sit amet consectetur gravida elementum dolor semper felis pulvinar <span class="text-no-wrap" data-w-id="88c6220c-13d2-dd35-3c23-98a368a55a43">feugiat risus.</span></p>' !!}</section>

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
