@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb15cd')
@section('bodyClass', 'body-18 height-auto')
@section('title', $seoTitle)
@section('metaDescription', $seoDescription)
@section('ogImage', $ogImage)

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Windows</div>
          </div>
        </div>
      </section>

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-vflex inner-container _500px---mbl center">
            <div class="mg-top-small">
              <h1 class="display-10 mid text-light">High-Quality Windows <br />for Your Home</h1>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <p class="paragraph-26">Upgrade your home with energy-efficient, durable, and stylish windows. Explore a full range of replacement and custom windows made for American homes.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="collection-list-wrapper-2 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
              @foreach($windows as $window)
                @include('partials.windows-index-card', ['window' => $window])
              @endforeach
            </div>
          </div>
        </div>
      </section>

@endsection

@section('bodyScripts')
    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow.js" type="text/javascript"></script>

    <script>
      (function () {
        const TRACK_PARAMS = [
          "utm_source","utm_medium","utm_campaign",
          "utm_term","utm_content","matchtype",
          "device","creative","gclid"
        ];
        const params = new URLSearchParams(window.location.search);
        const hasUtm = TRACK_PARAMS.some(p => params.get(p));
        if (hasUtm) {
          TRACK_PARAMS.forEach(param => {
            const value = params.get(param);
            if (value) localStorage.setItem(`lead_param_${param}`, value);
            else localStorage.removeItem(`lead_param_${param}`);
          });
        } else {
          const hasSavedUtm = TRACK_PARAMS.some(p => localStorage.getItem(`lead_param_${p}`));
          if (!hasSavedUtm) {
            const ref = document.referrer || "";
            let searchEngine = "", organicKeyword = "", refDomain = "";
            try {
              if (ref) {
                const refUrl = new URL(ref);
                refDomain = refUrl.hostname.replace(/^www\./, "");
                const SEO_ENGINES = {
                  "google.com": "google", "bing.com": "bing", "yahoo.com": "yahoo",
                  "duckduckgo.com": "duckduckgo", "yandex.ru": "yandex", "yandex.com": "yandex", "baidu.com": "baidu"
                };
                for (const [domain, name] of Object.entries(SEO_ENGINES)) {
                  if (refDomain.includes(domain)) {
                    searchEngine = name;
                    organicKeyword = refUrl.searchParams.get("q") || refUrl.searchParams.get("p") || refUrl.searchParams.get("query") || "(not provided)";
                    break;
                  }
                }
              }
            } catch (e) {}
            if (searchEngine) {
              localStorage.setItem("lead_param_utm_source", searchEngine);
              localStorage.setItem("lead_param_utm_medium", "organic");
              if (organicKeyword) localStorage.setItem("lead_param_utm_term", organicKeyword);
            } else if (refDomain && !refDomain.includes(window.location.hostname)) {
              localStorage.setItem("lead_param_utm_source", refDomain);
              localStorage.setItem("lead_param_utm_medium", "referral");
            } else {
              localStorage.setItem("lead_param_utm_source", "(direct)");
              localStorage.setItem("lead_param_utm_medium", "(none)");
            }
          }
        }
        if (!localStorage.getItem("lead_param_landing_page")) {
          localStorage.setItem("lead_param_landing_page", window.location.pathname);
        }
        function injectHiddenFields(form) {
          [...TRACK_PARAMS, "landing_page"].forEach(param => {
            if (!form.querySelector(`input[name="${param}"]`)) {
              const input = document.createElement("input");
              input.type = "hidden";
              input.name = param;
              input.value = localStorage.getItem(`lead_param_${param}`) || "";
              form.appendChild(input);
            }
          });
        }
        document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll("form").forEach(injectHiddenFields);
        });
      })();
    </script>
@endsection
