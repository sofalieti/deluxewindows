@extends('webflow.layouts.app')

@section('title', 'Gallery')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Gallery</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>gallery</code> | Path: <code>/gallery</code></p>

    <section class="mb-3">{!! '<h1 class="display-10 mid text-light">Photo Gallery<br data-w-id="6798db30-33e6-089b-7b6b-0c921e08dc37"></h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-26">Browse through our photo gallery showcasing Deluxe Windows\' windows and doors to spark your imagination. Whether you have a clear vision for your project or are just starting to gather ideas, these images can serve as your inspiration. <br data-w-id="054cb0fe-4fe5-53fc-19f6-0d44c3ae4d24"><br data-w-id="93db5089-2a02-9481-8aca-17e73e6f8df9"><sub data-w-id="e9f312e9-7549-e744-3d44-d9ced2524b3e">*Some projects shown were completed during Felix’s previous ownership and management role at another window company. All photographs were taken by him and are shared to highlight his professional experience.</sub><br data-w-id="618c0f38-2269-86e5-c3da-516d90db3e99"></p>' !!}</section>
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
