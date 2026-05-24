<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }} - {{ ucfirst($collectionSlug) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <a href="/" class="btn btn-outline-secondary btn-sm mb-3">Back to Home</a>
    <h1 class="mb-1">{{ data_get($fieldData, 'name', ucfirst($itemSlug)) }}</h1>
    <p class="text-muted mb-4">
        Collection: <code>{{ $collectionSlug }}</code>,
        Slug: <code>{{ $itemSlug }}</code>
    </p>

    @if(!empty(data_get($fieldData, 'description')))
        <div class="mb-4">
            {!! data_get($fieldData, 'description') !!}
        </div>
    @endif

    <section class="card">
        <div class="card-header">Field Data</div>
        <div class="card-body">
            <pre class="small mb-0">{{ json_encode($fieldData, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </section>
</main>
</body>
</html>
