        <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob sidebar-dropdown w-dropdown">
          <div data-dd="toggle" class="toggle-tab tabs-mob sidebar is-first brands w-dropdown-toggle" aria-haspopup="menu" aria-expanded="false" role="button" tabindex="0">
            <div class="toggle-text-tab-2 sidebar-txt">All collections</div>
            <div class="tab-icon-wrapper sidebar-icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 9 6" fill="none" class="sidebar-icon">
                <path d="M4.5 5.55005L0 1.05005L1.05 4.86076e-05L4.5 3.45005L7.95 4.86076e-05L9 1.05005L4.5 5.55005Z" fill="currentColor"></path>
              </svg>
            </div>
          </div>
          <nav data-dd="list" class="dropdown-list-4 sidebar-list w-dropdown-list">
            <div class="sidebar_content-wrapper-2 bottom brands">
              @if($logo)
              <img loading="lazy" src="{{ $logo }}" alt="{{ $name }}" class="svg50 sidebar-svg top-svg" />
              @endif
              <a href="/brands" class="all-brands-block w-inline-block" tabindex="0">
                <div class="icon-font-rounded arrow">&#xE824;</div>
                <div class="text-size-14">All brands</div>
              </a>
              <div class="scroll-block">
                @foreach($sidebarMaterialGroups as $group)
                  @if($group['visible'])
                  <div data-delay="0" data-hover="false" class="dropdown-2 w-dropdown">
                    <div class="dropdown-toogle-2 dd-toggle sidebar-toggle w-dropdown-toggle" aria-haspopup="menu" aria-expanded="false" role="button" tabindex="0">
                      <div class="{{ $group['sublabel'] ? 'text-block-42 ' : '' }}text-size-16 text-color-grey">{{ $group['name'] }}</div>
                      @if($group['sublabel'])
                      <div class="text-block-42 text-size-16 text-color-grey w-condition-invisible">{{ $group['sublabel'] }}</div>
                      @endif
                      <div class="icon-font-rounded-2 dropdown-arrow sidebar-icon hidden">&#x0494;</div>
                    </div>
                    <nav class="dropdown-list-2 dd-sidebar no-borders w-dropdown-list">
                      <div class="w-dyn-list">
                        <div role="list" class="dropdown-list-2 no-padding d-sidebar w-dyn-items">
                          @foreach($group['collections'] as $collection)
                          <div role="listitem" class="w-dyn-item">
                            <a href="/brand-collections/{{ $collection['slug'] }}" class="sidebar-item-2 w-inline-block">
                              @if($collection['image'])
                              <img loading="lazy" src="{{ $collection['image'] }}" alt="{{ $collection['name'] }}" class="sidebar-img" />
                              @endif
                              <div class="sidebar-txt text-size-16">{{ $collection['name'] }}</div>
                            </a>
                          </div>
                          @endforeach
                        </div>
                      </div>
                    </nav>
                  </div>
                  @endif

                  @if($group['insertWindowTypesAfter'] && $windowTypes->count() > 0)
                  <div data-delay="0" data-hover="false" class="dropdown-2 w-condition-invisible w-dropdown">
                    <div class="dropdown-toogle-2 dd-toggle sidebar-toggle w-dropdown-toggle" aria-haspopup="menu" aria-expanded="false" role="button" tabindex="0">
                      <div class="icon-font-rounded-2 dropdown-arrow sidebar-icon hidden">&#x0494;</div>
                    </div>
                    <nav class="dropdown-list-2 dd-sidebar no-borders w-dropdown-list">
                      <div class="w-dyn-list">
                        <div role="list" class="dropdown-list-2 no-padding d-sidebar w-dyn-items">
                          @foreach($windowTypes as $wt)
                          <div role="listitem" class="w-dyn-item">
                            <a href="/window-type/{{ $wt['slug'] }}" class="sidebar-item-2 w-inline-block">
                              @if($wt['image'])
                              <img loading="lazy" src="{{ $wt['image'] }}" alt="{{ $wt['name'] }}" class="sidebar-img" />
                              @endif
                              <div class="sidebar-txt text-size-16">{{ $wt['name'] }}</div>
                            </a>
                          </div>
                          @endforeach
                        </div>
                      </div>
                    </nav>
                  </div>
                  @endif
                @endforeach
              </div>
            </div>

            @include('partials.brand-sidebar-form-card', ['variant' => 'brands'])
          </nav>
        </div>
