    <script>
      (function () {
        const TRACK_PARAMS = [
          "utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term",
          "matchtype", "device", "creative", "gclid", "fbclid", "msclkid"
        ];
        const urlParams = new URLSearchParams(window.location.search);
        TRACK_PARAMS.forEach(param => {
          const val = urlParams.get(param);
          if (val) localStorage.setItem("lead_param_" + param, val);
        });
        // Alias used in some Google Ads final-URL suffixes.
        if (!localStorage.getItem("lead_param_creative")) {
          const utmCreative = urlParams.get("utm_creative");
          if (utmCreative) localStorage.setItem("lead_param_creative", utmCreative);
        }
        if (!localStorage.getItem("lead_param_landing_page")) {
          localStorage.setItem("lead_param_landing_page", window.location.pathname);
        }
      })();
    </script>
