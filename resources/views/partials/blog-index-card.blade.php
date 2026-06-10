<div role="listitem" class="w-dyn-item">
  <a
    data-w-id="6b75635b-0b9a-d6c6-b93b-fdfac87ff1d3"
    href="/blog/{{ $post['slug'] }}"
    class="blog-card-wrapper-v1 w-inline-block"
  >
    <div class="blog-card-top-content-v1">
      <div class="image-wrapper border-radius-image-default blog-card-top-content-v1---image">
        @if(!empty($post['image']))
        <x-img
          :src="$post['image']"
          preset="card"
          :loading="$loading ?? 'lazy'"
          :alt="$post['name']"
          class="image cover-image"
        />
        @endif
      </div>
    </div>
    <div class="blog-card-bottom-content-v1">
      <div class="inner-container _400px---mbl">
        <h3 class="heading-18">{{ $post['name'] }}</h3>
      </div>
      <div class="mg-top-default">
        <div class="blog-details-wrapper-v1">
          @if(!empty($post['published']))
          <div class="card-feature-wrapper">
            <img
              src="/webflow-assets/images/6841ddf8ace3d9d9facb1894_calendar-icon-property-x-webflow-template.svg"
              loading="eager"
              alt="Calendar icon"
            />
            <div class="text-neutral-light"><div>{{ $post['published'] }}</div></div>
          </div>
          @endif
          <div class="link mid"><div>Read more</div></div>
        </div>
      </div>
    </div>
  </a>
</div>
