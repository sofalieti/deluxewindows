{{-- Site-wide company schema (same on every page). Not page-specific. --}}
<script type="application/ld+json">{!! json_encode(
    \App\Services\Seo\OrganizationSchema::toArray(),
    JSON_UNESCAPED_SLASHES
    | JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
) !!}</script>
