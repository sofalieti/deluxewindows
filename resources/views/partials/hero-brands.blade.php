{{--
  Home hero brands row — variant "new" (?new-brands=true).
  Desktop: right under the hero headline/phones. Mobile: under the call button
  (placement "promo" inside hero-mobile-promo, "nm" in the new mobile hero).
  Logos are knocked out to white over the photo (dark on the white mobile hero)
  and scroll as a marquee like the original strip (three copies for a seamless loop).
--}}
@php($heroBrandItems = brand_strip_items())
@php($heroBrandsPlacementClass = 'hero-brands--'.($heroBrandsPlacement ?? 'desktop'))
<div class="hero-brands {{ $heroBrandsPlacementClass }}" aria-label="Brands we install">
  <div class="hero-brands__viewport">
    <ul class="hero-brands__list">
      @for($copy = 0; $copy < 3; $copy++)
        @foreach($heroBrandItems as $item)
          <li class="hero-brands__item" @if($copy > 0) aria-hidden="true" @endif>
            <a href="{{ $item['href'] }}" class="hero-brands__link" title="{{ $item['alt'] }}" @if($copy > 0) tabindex="-1" @endif>
              <x-img :src="$item['hero_image'] ?? $item['image']" preset="brand_grid" :alt="$copy === 0 ? $item['alt'] : ''" loading="lazy" class="hero-brands__logo" />
            </a>
          </li>
        @endforeach
      @endfor
    </ul>
  </div>
</div>
