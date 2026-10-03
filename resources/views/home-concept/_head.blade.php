@php
  $variantCss = public_path('webflow-overrides/home-concept/'.$c['variant'].'.css');
  $baseCss = public_path('webflow-overrides/home-concept/base.css');
  $v1 = is_file($variantCss) ? (string) filemtime($variantCss) : '1';
  $v0 = is_file($baseCss) ? (string) filemtime($baseCss) : '1';
@endphp
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="theme-color" content="{{ $themeColor ?? '#111111' }}" />

<script async src="https://www.googletagmanager.com/gtag/js?id=G-JHYBB0THJM"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag() { dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', 'G-JHYBB0THJM');
  gtag('config', 'AW-1030787786');
  function gtag_report_conversion(url, user) {
    var callback = function () { if (typeof url !== 'undefined') { window.location = url; } };
    gtag('event', 'conversion', { 'send_to': 'AW-1030787786/Hs9eCP7MwngQyqXC6wM', 'event_callback': callback });
    if (typeof window.uet_report_conversion === 'function') { window.uet_report_conversion(user); }
    return false;
  }
</script>

@include('partials.seo-head')

<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="{{ $fonts }}" rel="stylesheet" />
<link href="/webflow-overrides/home-concept/base.css?v={{ $v0 }}" rel="stylesheet" />
<link href="/webflow-overrides/home-concept/{{ $c['variant'] }}.css?v={{ $v1 }}" rel="stylesheet" />
<link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
