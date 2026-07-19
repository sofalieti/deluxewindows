<div class="dropdown-tab tabs-mob sidebar-dropdown">
  <div class="toggle-tab tabs-mob sidebar is-first brands" aria-hidden="true">
    <div class="toggle-text-tab-2 sidebar-txt">{{ $sidebarLabel ?? 'All collections' }}</div>
  </div>
  <nav class="dropdown-list-4 sidebar-list" data-dd="list">
    <div class="sidebar_content-wrapper-2 bottom brands">
      @if($logo)
      <x-img :src="$logo" preset="logo" :alt="$name" loading="lazy" class="svg50 sidebar-svg top-svg" />
      @endif
      <a href="{{ $allBrandsHref ?? '/brands' }}" class="all-brands-block w-inline-block" tabindex="0">
        <div class="icon-font-rounded arrow">&#xE824;</div>
        <div class="text-size-14">All brands</div>
      </a>
      <div class="scroll-block">
        @foreach($sidebarMaterialGroups as $group)
          @if($group['visible'])
          <div class="sidebar-material-group">
            <div class="sidebar-material-heading">
              <div class="{{ $group['sublabel'] ? 'text-block-42 ' : '' }}text-size-16 text-color-grey">{{ $group['name'] }}</div>
              @if($group['sublabel'])
              <div class="text-block-42 text-size-16 text-color-grey">{{ $group['sublabel'] }}</div>
              @endif
            </div>
            <div class="w-dyn-list">
              <div role="list" class="dropdown-list-2 no-padding d-sidebar w-dyn-items">
                @foreach($group['collections'] as $collection)
                <div role="listitem" class="w-dyn-item">
                  <a href="/brand-collections/{{ $collection['slug'] }}" class="sidebar-item-2 w-inline-block{{ !empty($currentCollectionSlug) && $currentCollectionSlug === $collection['slug'] ? ' w--current' : '' }}">
                    @if($collection['image'])
                    <x-img :src="$collection['image']" preset="sidebar" :alt="$collection['name']" loading="lazy" class="sidebar-img" />
                    @endif
                    <div class="sidebar-txt text-size-16">{{ $collection['name'] }}</div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
          @endif
        @endforeach
      </div>
    </div>

    @if(empty($hideSidebarInlineForm))
    @include('partials.brand-sidebar-form-card', [
      'variant' => 'brands',
      'wfPageId' => $wfPageId ?? null,
    ])
    @endif
  </nav>
</div>
