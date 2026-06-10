    <section class="section">
      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-vflex inner-container _500px---mbl center alloha">
            <div class="mg-top-small">
              <h2 class="display-10 mid text-light">Windows Types We Install in <br /></h2>
            </div>
            <div class="mg-top-small">
              <h2 class="display-10 mid text-light">{{ $cityName }}</h2>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <div class="asdf w-embed">Every material, every style — installed to Title 24 energy standards by our certified {{ $cityName }} team.</div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="collection-list-wrapper-2 w-dyn-list">
          <div role="list" class="collection-list-14 w-dyn-items">
            @foreach($windowTypes as $window)
            <div role="listitem" class="collection-item-13 new w-dyn-item">
              <a href="/windows/{{ $window['slug'] }}" class="property-wrapper-v1 w-inline-block">
                <div class="property-card-top-content-v1">
                  <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                    @if($window['image'])
                    <img src="{{ thumbnail_url($window['image'], 'card') }}" loading="eager" alt="{{ $window['name'] }}" class="image cover-image" />
                    @endif
                  </div>
                  <div class="badge-wrapper---top-left"></div>
                </div>
                <div class="property-card-bottom-content-v1">
                  <div>
                    <h2 class="display-5">{{ $window['name'] }}</h2>
                  </div>
                </div>
              </a>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>
