@extends('webflow.layouts.app')

@section('title', 'Coupons Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Coupons Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_coupons</code> | Path: <code>/coupons</code></p>

    <section class="mb-3">{!! '<div class="display-10 mid text-inline">&nbsp;posts</div>' !!}</section>
    <section class="mb-3">{!! '<div class="blog-icon-text"></div>' !!}</section>
    <section class="mb-3">{!! '<div>All</div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<div>Read more</div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>

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
