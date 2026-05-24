@extends('webflow.layouts.app')

@section('title', 'Terms')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Terms</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>terms</code> | Path: <code>/terms</code></p>

    <section class="mb-3">{!! '<h1 class="display-10 mid text-light">Terms of Use<br data-w-id="b59b2995-47d6-52f6-0a2e-7832f5ff039a"></h1>' !!}</section>
    <section class="mb-3">{!! '<a href="#editing-pages" class="template-pages---nav-item-link">Terms</a>' !!}</section>
    <section class="mb-3">{!! '<h2 id="terms" class="mg-bottom-small">Terms</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="mg-bottom-default"><strong data-w-id="e64ac492-2a5e-3eab-ebe0-409012aec31c">Portfolio &amp; Image Use Disclaimer<br data-w-id="4eaa7e3f-1ce0-ec57-5a38-6786fa0740eb"><br data-w-id="bdb769a5-9130-7a99-5f53-08db75e07b5f">‍</strong>Some photographs and project images displayed on this website reflect work personally managed and photographed by Felix during his prior professional experience in the window and door industry, before Deluxe Windows. These images are presented solely for the purpose of illustrating his background, workmanship, and expertise.Deluxe Windows makes no claim that all such projects were performed under the Deluxe Windows business entity. Where applicable, projects completed under prior business arrangements are clearly included as part of Felix’s professional portfolio.Unless otherwise noted, Deluxe Windows owns or has the rights to use all images, content, and materials displayed on this website. No imagery or content may be copied, reproduced, or distributed without prior written consent.<br data-w-id="fe543b50-6409-ef51-d616-c47076f0f0dd"></p>' !!}</section>

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
