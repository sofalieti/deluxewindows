<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }} | Windows</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4 py-md-5">
    <a href="/windows" class="btn btn-outline-secondary btn-sm mb-4">Back to windows</a>

    <section class="row g-4 align-items-start">
        <div class="col-12 col-lg-7">
            <h1 class="display-6 mb-3">{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }}</h1>
            @if(!empty(data_get($fieldData, 'short-title')))
                <p class="lead text-secondary mb-3">{{ data_get($fieldData, 'short-title') }}</p>
            @endif

            @if(!empty(data_get($fieldData, 'property-listing---summary')))
                <p class="mb-4">{{ data_get($fieldData, 'property-listing---summary') }}</p>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5 mb-3">Request a free quote</h2>
                    <form>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Full name</label>
                                <input type="text" class="form-control" placeholder="Your name">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" placeholder="(650) 000-0000">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" placeholder="you@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Details</label>
                                <textarea class="form-control" rows="3" placeholder="Tell us about your project"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="button" class="btn btn-dark w-100">Send request</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <aside class="card border-0 shadow-sm bg-light">
                <div class="card-body p-4">
                    <p class="text-uppercase fw-semibold text-muted small mb-2">Special offer</p>
                    <h3 class="h4 mb-3">40% off for limited time</h3>

                    @if(!empty(data_get($fieldData, 'discounttext')))
                        <div class="mb-3">{!! data_get($fieldData, 'discounttext') !!}</div>
                    @else
                        <p class="mb-0">Starting from special price per window installed.</p>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    @if(!empty(data_get($fieldData, 'property-listing---about')))
        <section class="mt-5">
            {!! data_get($fieldData, 'property-listing---about') !!}
        </section>
    @endif
</main>
</body>
</html>
