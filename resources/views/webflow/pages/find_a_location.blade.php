@extends('webflow.layouts.app')

@section('title', 'Find a location')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Find a location</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>find-a-location</code> | Path: <code>/find-a-location</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid">Find Previous Jobs<br data-w-id="f3722c30-b474-0433-0150-ca99ba81f4fe"></h1>' !!}</section>

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
