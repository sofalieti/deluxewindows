      <div class="f-section-large-2">
        <img
          src="/webflow-assets/images/69986e6eb764fe1619060131_achievement-award-medal-icon.svg"
          loading="lazy"
          width="100"
          alt=""
        />
        <div class="f-container-regular-2">
          <div class="text-center---mbl">
            <div class="title-left---content-right">
              <div class="width-100-mobile-landscape w-clearfix"><h2 class="display-8 mid">Our Certifications</h2></div>
            </div>
          </div>
          <p class="f-paragraph-large">Factory trained and certified installers, AAMA certified.</p>
          <div class="w-layout-grid f-grid-three-column">
            <div class="div-block-58">
              <div class="f-feature-icon-wrapper">
                <img
                  loading="lazy"
                  src="/webflow-assets/images/6998617839debbabce241e8e_6915aaca08003de3e1e57018_marvin-logo-black.svg"
                  alt=""
                  class="image-34"
                />
              </div>
            </div>
            <div>
              <div class="f-feature-icon-wrapper">
                <img
                  width="329"
                  loading="lazy"
                  alt=""
                  src="/webflow-assets/images/69200ccf66431025ccaabea5_milgard.svg"
                  class="image-35"
                />
              </div>
            </div>
            <div>
              <div class="f-feature-icon-wrapper">
                <img
                  loading="lazy"
                  src="/webflow-assets/images/69986178667bcb1013476512_6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg"
                  alt=""
                  class="image-36"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="previous-jobs-map-section">
        <div class="title-left---content-right dva"><h2 class="heading-23">Our Previous Jobs</h2></div>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" crossorigin="" />
        <div id="previous-jobs-map" class="jobs-map" aria-label="Map of our previous installation jobs"></div>
      </div>
      @push('scripts')
        @once
          <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
          <script defer src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js" crossorigin=""></script>
          <script defer src="/webflow-overrides/previous-jobs-map.js"></script>
        @endonce
      @endpush