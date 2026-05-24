@extends('webflow.layouts.app')

@section('title', 'Testimonials')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Testimonials</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>testimonials</code> | Path: <code>/testimonials</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid">Look at <br data-w-id="91409f03-0dbd-db0a-364d-1facf3ca9c83">What People Say <span class="text-no-wrap" data-w-id="b95f405a-5af8-c398-ccfa-f2202ab2e13c">About Us</span><br data-w-id="b95f405a-5af8-c398-ccfa-f2202ab2e13e"></h1>' !!}</section>

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
