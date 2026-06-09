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
              <img loading="lazy" src="{{ $logo }}" alt="{{ $name }}" class="svg50 sidebar-svg top-svg" width="300" height="150" />
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

            {{-- Sidebar form (mobile) --}}
            <div class="card-2 sidebar-v1---card new-design brands">
              <div class="form-sidebar">
                <div class="form-block-3 w-form">
                  <form name="wf-form-Brand-Sidebar" method="get" class="form-wrapper" aria-label="Brand Form">
                    <div class="grid-1-column-2 gap-row-12">
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF416;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Name" placeholder="Full name" type="text" required />
                      </div>
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF40F;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Email" placeholder="Email address" type="email" required />
                      </div>
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF0B3;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Phone" placeholder="Phone number" type="tel" required />
                      </div>
                      <div class="input-wrapper-5">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Subject" placeholder="City" type="text" required />
                        <div class="input-line-icon-wrapper">
                          <img loading="lazy" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon" style="width:18px;height:18px;object-fit:contain;" />
                        </div>
                      </div>
                      <div class="primary-button-6 space-between-v1">
                        <input type="submit" data-wait="Please wait..." class="inside-input-button-4 text-light w-button" value="Get Your Free Estimate" />
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </nav>
        </div>
