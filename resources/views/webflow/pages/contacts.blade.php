@extends('webflow.layouts.app')

@section('title', 'Contacts')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Contacts</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>contacts</code> | Path: <code>/contacts</code></p>

    <section class="mb-3">{!! '<h1 class="heading-31">Contact Us</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs. We cover the entire Bay Area.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">San Francisco/North Bay</div>' !!}</section>
    <section class="mb-3">{!! '<div>(415) 651-2321</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">San Mateo/Burlingame (Peninsula)</div>' !!}</section>
    <section class="mb-3">{!! '<div>(650) 461-4446</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">East Bay</div>' !!}</section>
    <section class="mb-3">{!! '<div>(510) 244-6500</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">South Bay</div>' !!}</section>
    <section class="mb-3">{!! '<div>(408) 516-1200</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Lamorinda</div>' !!}</section>
    <section class="mb-3">{!! '<div>(925) 430-5135</div>' !!}</section>

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
