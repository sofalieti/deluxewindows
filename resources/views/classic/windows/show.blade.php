<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle ?? $title }}</title>
    @if(!empty($seoDescription))
    <meta name="description" content="{{ $seoDescription }}">
    @endif
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/webflow-overrides/classic-shared.css?v=1" rel="stylesheet" type="text/css">
</head>
<body>
    <main class="container py-4 classic-window-page">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item">Windows</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
            </ol>
        </nav>

        <section class="row g-4 align-items-start mb-4">
            <div class="col-12 col-lg-7">
                <h1 class="display-6 fw-bold mb-3">{{ $title }}</h1>
                @if(!empty($shortTitle) && $shortTitle !== $title)
                <p class="text-muted mb-2">{{ $shortTitle }}</p>
                @endif
                @if(!empty($summary))
                <p class="lead mb-3">{{ $summary }}</p>
                @endif
                @if(!empty($discountHtml))
                <div class="classic-rich mb-0">
                    {!! $discountHtml !!}
                </div>
                @endif
            </div>
            <div class="col-12 col-lg-5">
                @if(!empty($heroImage))
                <img src="{{ $heroImage }}" alt="{{ $title }}" class="img-fluid rounded-4 classic-hero-image">
                @endif
            </div>
        </section>

        @if(!empty($aboutHtml))
        <section class="classic-card p-4 mb-4">
            <div class="classic-rich">
                {!! $aboutHtml !!}
            </div>
        </section>
        @endif

        @if(!empty($warrantyHtml))
        <section class="classic-card p-4 mb-4">
            <h2 class="h4 mb-3">Our Guarantee</h2>
            <div class="classic-rich">
                {!! $warrantyHtml !!}
            </div>
        </section>
        @endif

        @if(($galleryImages ?? collect())->isNotEmpty())
        <section class="mb-4">
            <h2 class="h4 mb-3">Gallery</h2>
            <div class="row g-3">
                @foreach($galleryImages as $imageUrl)
                <div class="col-6 col-md-4 col-lg-3">
                    <img src="{{ $imageUrl }}" alt="{{ $title }} gallery image" class="img-fluid rounded-3 classic-gallery-image">
                </div>
                @endforeach
            </div>
        </section>
        @endif

        @if(($brands ?? collect())->isNotEmpty())
        <section class="mb-4">
            <h2 class="h4 mb-3">{{ $titleForBrands ?: ('Top '.$title.' Brands') }}</h2>
            <div class="row g-3">
                @foreach($brands as $brand)
                <div class="col-6 col-md-4 col-lg-3">
                    <a class="classic-card classic-link-card p-3 h-100 d-flex flex-column justify-content-center text-decoration-none" href="/brands/{{ $brand['slug'] }}">
                        @if(!empty($brand['image']))
                        <img src="{{ $brand['image'] }}" alt="{{ $brand['name'] }}" class="img-fluid mb-2 brand-card-logo">
                        @endif
                        <span class="fw-semibold">{{ $brand['name'] }}</span>
                    </a>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        @if(($brandTypes ?? collect())->isNotEmpty())
        <section class="mb-4">
            <h2 class="h4 mb-3">Learn More About Different Window Types</h2>
            <div class="row g-3">
                @foreach($brandTypes as $type)
                <div class="col-6 col-md-4 col-lg-3">
                    <a class="classic-card classic-link-card p-3 h-100 d-flex flex-column justify-content-center text-decoration-none" href="/window-type/{{ $type['slug'] }}">
                        @if(!empty($type['image']))
                        <img src="{{ $type['image'] }}" alt="{{ $type['name'] }}" class="img-fluid mb-2 brand-card-logo">
                        @endif
                        <span class="fw-semibold">{{ $type['name'] }}</span>
                    </a>
                </div>
                @endforeach
            </div>
        </section>
        @endif
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
