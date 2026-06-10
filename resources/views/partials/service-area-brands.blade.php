    <section class="section">
      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-vflex inner-container _500px---mbl center alloha">
            <div class="mg-top-small">
              <h2 class="display-10 mid text-light">Window Brands We Install in<br /></h2>
            </div>
            <div class="mg-top-small">
              <h2 class="display-10 mid text-light">{{ $cityName }}</h2>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <div class="asdf w-embed">We carry every major brand — match any budget, any style, any home in {{ $cityName }}.</div>
              </div>
            </div>
          </div>
        </div>
      </section>
      @if($featuredBrands->count() > 0)
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="div-block-51 asdfsdf">
          <div class="collection-list-wrapper-19 w-dyn-list">
            <div role="list" class="collection-list-12 w-dyn-items w-row">
              @foreach($featuredBrands as $brand)
              <div role="listitem" class="collection-item-12 w-dyn-item w-col w-col-6">
                <a href="/brands/{{ $brand['slug'] }}" class="brand-link w-inline-block">
                  @if($brand['logo'])
                  <img src="{{ $brand['logo'] }}" loading="lazy" alt="{{ $brand['name'] }}" />
                  @endif
                </a>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      @endif
    </section>
