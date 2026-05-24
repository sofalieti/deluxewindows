@extends('webflow.layouts.app')

@section('title', 'Global Settings Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Global Settings Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_global-settings</code> | Path: <code>/global-settings</code></p>

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
