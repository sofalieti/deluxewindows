@extends('webflow.layouts.app')

@section('title', '🏷️ Categories Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🏷️ Categories Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_category</code> | Path: <code>/category</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid text-inline"></h1>' !!}</section>
    <section class="mb-3">{!! '<div class="display-9 mid text-inline">&nbsp;plans</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid"></h2>' !!}</section>
    <section class="mb-3">{!! '<p></p>' !!}</section>
    <section class="mb-3">{!! '<div class="display-7 mid text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2 mid text-neutral-light">/mo</div>' !!}</section>
    <section class="mb-3">{!! '<div>Read more</div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-3 mid">What’s included?</div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div>Purchase <span class="text-no-wrap" data-w-id="818e6658-909b-018d-e3d4-f3603a129d65">credit now</span></div>' !!}</section>
    <section class="mb-3">{!! '<div class="base-icon-font"></div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>

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
