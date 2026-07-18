<title>{{ $pageMetadata->title }}</title>
<meta name="description" content="{{ $pageMetadata->description }}" />
<meta name="robots" content="{{ $pageMetadata->robots }}" />
<link rel="canonical" href="{{ $pageMetadata->canonical }}" />

<meta property="og:title" content="{{ $pageMetadata->ogTitle }}" />
<meta property="og:description" content="{{ $pageMetadata->ogDescription }}" />
<meta property="og:url" content="{{ $pageMetadata->canonical }}" />
<meta property="og:type" content="{{ $pageMetadata->ogType }}" />
<meta property="og:site_name" content="Deluxe Windows" />
@if($pageMetadata->ogImage)
<meta property="og:image" content="{{ $pageMetadata->ogImage }}" />
@endif

<meta name="twitter:title" content="{{ $pageMetadata->twitterTitle }}" />
<meta name="twitter:description" content="{{ $pageMetadata->twitterDescription }}" />
<meta name="twitter:card" content="{{ $pageMetadata->twitterCard }}" />
@if($pageMetadata->twitterImage)
<meta name="twitter:image" content="{{ $pageMetadata->twitterImage }}" />
@endif

@foreach($pageSchemas as $schema)
<script type="application/ld+json">{!! json_encode(
    $schema,
    JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) !!}</script>
@endforeach
