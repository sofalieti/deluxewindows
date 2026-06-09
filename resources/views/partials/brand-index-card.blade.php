@php
  $featureIcons = [
    '<path d="M4.08464 9.8348L8.66797 2.3348L13.2513 9.8348H4.08464ZM13.2513 19.0015C12.2096 19.0015 11.3242 18.6369 10.5951 17.9077C9.86589 17.1785 9.5013 16.2931 9.5013 15.2515C9.5013 14.2098 9.86589 13.3244 10.5951 12.5952C11.3242 11.866 12.2096 11.5015 13.2513 11.5015C14.293 11.5015 15.1784 11.866 15.9076 12.5952C16.6367 13.3244 17.0013 14.2098 17.0013 15.2515C17.0013 16.2931 16.6367 17.1785 15.9076 17.9077C15.1784 18.6369 14.293 19.0015 13.2513 19.0015ZM1.16797 18.5848V11.9181H7.83464V18.5848H1.16797ZM13.2513 17.3348C13.8346 17.3348 14.3277 17.1334 14.7305 16.7306C15.1332 16.3279 15.3346 15.8348 15.3346 15.2515C15.3346 14.6681 15.1332 14.1751 14.7305 13.7723C14.3277 13.3695 13.8346 13.1681 13.2513 13.1681C12.668 13.1681 12.1749 13.3695 11.7721 13.7723C11.3694 14.1751 11.168 14.6681 11.168 15.2515C11.168 15.8348 11.3694 16.3279 11.7721 16.7306C12.1749 17.1334 12.668 17.3348 13.2513 17.3348ZM2.83464 16.9181H6.16797V13.5848H2.83464V16.9181ZM7.04297 8.16813H10.293L8.66797 5.54313L7.04297 8.16813Z" fill="#1E73B9"></path>',
    '<path d="M7.4598 15.8348L11.7723 10.6681H8.43896L9.04313 5.93896L5.18896 11.5015H8.0848L7.4598 15.8348ZM5.3348 19.0015L6.16813 13.1681H2.00146L9.50146 2.3348H11.1681L10.3348 9.00146H15.3348L7.00146 19.0015H5.3348Z" fill="#1E73B9"></path>',
    '<path d="M8.66797 18.2095L1.16797 12.3761L2.54297 11.3345L8.66797 16.0845L14.793 11.3345L16.168 12.3761L8.66797 18.2095ZM8.66797 14.0011L1.16797 8.16781L8.66797 2.33447L16.168 8.16781L8.66797 14.0011ZM8.66797 11.8761L13.4596 8.16781L8.66797 4.45947L3.8763 8.16781L8.66797 11.8761Z" fill="#1E73B9"></path>',
    '<path d="M7.79313 13.6265L12.5015 8.91813L11.314 7.73063L7.79313 11.2515L6.04313 9.50146L4.85563 10.689L7.79313 13.6265ZM8.66813 19.0015C6.73758 18.5154 5.14383 17.4077 3.88688 15.6785C2.62994 13.9494 2.00146 12.0292 2.00146 9.91813V4.8348L8.66813 2.3348L15.3348 4.8348V9.91813C15.3348 12.0292 14.7063 13.9494 13.4494 15.6785C12.1924 17.4077 10.5987 18.5154 8.66813 19.0015ZM8.66813 17.2515C10.1126 16.7931 11.307 15.8765 12.2515 14.5015C13.1959 13.1265 13.6681 11.5987 13.6681 9.91813V5.98063L8.66813 4.10563L3.66813 5.98063V9.91813C3.66813 11.5987 4.14035 13.1265 5.0848 14.5015C6.02924 15.8765 7.22369 16.7931 8.66813 17.2515Z" fill="#1E73B9"></path>',
  ];
@endphp

<div role="listitem" class="collection-item-11 w-dyn-item">
  <div class="brand-card">
    <div>
      <a href="/brands/{{ $brand['slug'] }}" class="image-wrapper border-radius-image-default property-card-top-content-v1---image is-brand-logo w-inline-block">
        @if($brand['logo'])
        <img src="{{ $brand['logo'] }}" loading="eager" alt="" class="image cover-image is-brandlogo" />
        @endif
      </a>
      <div class="brand_card-content">
        @if(count($brand['materials']))
        <div class="w-dyn-list">
          <div role="list" class="tag-wrapper w-dyn-items">
            @foreach($brand['materials'] as $material)
            <div role="listitem" class="material-tag w-dyn-item">
              <a fs-list-field="materials" fs-list-value="{{ $material['filter_value'] }}" href="/windows/{{ $material['slug'] }}" class="material-tag-btn w-button">{{ $material['name'] }}</a>
            </div>
            @endforeach
          </div>
        </div>
        @endif
        @foreach($brand['features'] as $index => $feature)
        <div class="brand_content-wrapper">
          <div class="brand_content-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 20 24" fill="none" class="brand-icon">{!! $featureIcons[$index] ?? $featureIcons[0] !!}</svg>
            <div class="brand_item-txt">
              <div class="brand_item-title">{{ $feature['title'] }}</div>
              <div class="brand_item-subtitle">{{ $feature['text'] }}</div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
    <div class="brand_card-content is-last">
      <div class="brand_link-wrapper">
        <a href="/brands/{{ $brand['slug'] }}" class="all-brands-block is-brand w-inline-block">
          <div class="text-size-14 tezt-color-orange">View collections</div>
          <div class="icon-font-rounded arrow no-rotation"></div>
        </a>
        <div class="price-range-hidden">
          @foreach($brand['price_slots'] as $slot)
          <div>
            @if($slot['active'])
            <div fs-list-field="{{ $slot['field'] }}">{{ $slot['label'] }}</div>
            @endif
          </div>
          @endforeach
        </div>
        <div class="brand_item-title">{{ $brand['price_range'] }}</div>
      </div>
    </div>
  </div>
</div>
