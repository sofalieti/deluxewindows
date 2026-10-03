@php
  $jsPath = public_path('webflow-overrides/home-concept/concept.js');
  $jsV = is_file($jsPath) ? (string) filemtime($jsPath) : '1';
@endphp
@include('partials.attribution-tracking')
@include('partials.visit-tracking')
@include('partials.service-area-personalization')
@include('partials.lead-form-scripts')
@include('partials.phone-click-scripts')
<script src="/webflow-overrides/home-concept/concept.js?v={{ $jsV }}" defer></script>
