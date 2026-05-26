      <section class="section">
        <div class="text-center---mbl">
          <div class="title-left---content-right">
            <div class="width-100-mobile-landscape">
              <h2 class="heading-46">Discover Different <br />Window Options</h2>
            </div>
          </div>
        </div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns posts-right---grid">
            <div id="w-node-_87779e2a-0c09-17ad-e055-dbd837711a32-fd53ec90" class="collection-list-wrapper w-dyn-list">
              <div role="list" class="grid-1-column properties-grid---v3 w-dyn-items">
                @foreach($homeWindows ?? [] as $hw)
                <div
                  id="w-node-_87779e2a-0c09-17ad-e055-dbd837711a34-fd53ec90"
                  role="listitem"
                  class="collection-item-2 w-dyn-item"
                >
                  <a href="/windows/{{ $hw['slug'] }}" class="property-wrapper-v3 w-inline-block"
                    ><div id="w-node-_87779e2a-0c09-17ad-e055-dbd837711a36-fd53ec90" class="position-relative">
                      <div class="image-wrapper border-radius-image-default height-100 wrapperimage2">
                        @if($hw['image'])
                        <img
                          src="{{ $hw['image'] }}"
                          loading="eager"
                          alt="{{ $hw['name'] }}"
                          class="image cover-image property-wrapper-v3---image"
                        />
                        @endif
                      </div>
                      <div class="badge-wrapper---top-left"></div>
                    </div>
                    <div
                      id="w-node-_87779e2a-0c09-17ad-e055-dbd837711a3a-fd53ec90"
                      class="inner-container _450px---mbl"
                    >
                      <h3 class="display-5 mid">{{ $hw['name'] }}</h3>
                      @if($hw['summary'])
                      <div class="mg-top-small">
                        <div class="text-paragraph">
                          <p class="paragraph-49">{{ $hw['summary'] }}</p>
                        </div>
                      </div>
                      @endif
                    </div
                  ></a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
        <div class="buttons-row">
          <a
            id="w-node-e9da5fd6-ab44-5b2f-81ad-6e5f36bfab9c-36bfab9c"
            href="/windows"
            class="primary-button w-inline-block"
            ><div class="text-block-22">See all windows</div></a
          >
        </div>
      </section>