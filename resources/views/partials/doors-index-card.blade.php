<div data-w-id="bccf80b9-61e7-0c10-40c8-ee06b0ef47ee" role="listitem" class="w-dyn-item">
  <a
    data-w-id="806c7dc5-3c7c-dd9e-7fbe-2d389e75a275"
    href="/doors/{{ $door['slug'] }}"
    class="property-wrapper-v1 w-inline-block"
  >
    <div class="property-card-top-content-v1">
      <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
        @if(!empty($door['image']))
        <x-img
          :src="$door['image']"
          preset="card"
          loading="eager"
          :alt="$door['name']"
          class="image cover-image"
        />
        @endif
      </div>
      <div class="badge-wrapper---top-left"></div>
    </div>
    <div class="property-card-bottom-content-v1">
      <div>
        <h2 class="display-5">{{ $door['name'] }}</h2>
        <div class="mg-top-extra-small w-condition-invisible">
          <div class="card-feature-wrapper">
            <img
              src="/webflow-assets/images/6841ddf8ace3d9d9facb1875_location-black-icon-property-x-webflow-template.svg"
              loading="eager"
              alt="Location Icon - Property X Webflow Template"
            />
            <div class="text-titles"><div class="w-dyn-bind-empty"></div></div>
          </div>
        </div>
      </div>
    </div>
  </a>
</div>
