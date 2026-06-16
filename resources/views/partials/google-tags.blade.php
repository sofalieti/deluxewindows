@php
  $ga4Id = config('services.google.ga4_id');
  $adsId = config('services.google.ads_id');
  $conversionSendTo = config('services.google.conversion_send_to');
@endphp
<script>
  (function () {
    const ga4Id = @json($ga4Id);
    const adsId = @json($adsId);
    const conversionSendTo = @json($conversionSendTo);
    let gtagLoaded = false;

    function ensureGtagLoaded() {
      if (gtagLoaded || !ga4Id) return;
      gtagLoaded = true;
      const script = document.createElement('script');
      script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ga4Id);
      script.async = true;
      document.head.appendChild(script);
      window.dataLayer = window.dataLayer || [];
      window.gtag = window.gtag || function () { dataLayer.push(arguments); };
      window.gtag('js', new Date());
      window.gtag('config', ga4Id);
      if (adsId) {
        window.gtag('config', adsId);
      }
    }

    window.gtag_report_conversion = function (url) {
      ensureGtagLoaded();
      const callback = function () {
        if (typeof url !== 'undefined') {
          window.location = url;
        }
      };
      if (window.gtag && conversionSendTo) {
        window.gtag('event', 'conversion', {
          send_to: conversionSendTo,
          event_callback: callback
        });
      } else {
        callback();
      }
      return false;
    };

    window.addEventListener('scroll', ensureGtagLoaded, { once: true });
    window.addEventListener('click', ensureGtagLoaded, { once: true });
    setTimeout(ensureGtagLoaded, 3000);
  })();
</script>

