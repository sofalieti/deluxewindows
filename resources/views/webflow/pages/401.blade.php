@extends('webflow.layouts.app')

@section('title', '🔐 Password Protected')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🔐 Password Protected</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>401</code> | Path: <code>/401</code></p>

    <section class="mb-3">{!! '<h1>Pasword protected</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="mg-top-small">Lorem ipsum dolor sit amet consectetur gravida elementum dolor semper felis pulvinar <span class="text-no-wrap" data-w-id="017a525a-83f0-5389-33fb-ffcab5156a44">feugiat risus.</span></p>' !!}</section>
    <section class="mb-3">{!! '<label for="pass" class="hidden">Password</label>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div>Incorrect password. Please try again.</div>' !!}</section>

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
