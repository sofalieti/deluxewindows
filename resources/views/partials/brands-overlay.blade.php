{{--
  Home page brands — variant "new" (?new-brands=true).
  A white card that overlaps the bottom edge of the hero and shows every logo
  as a static grid, so the brands stay readable on all breakpoints (no marquee,
  no horizontal scroll).
--}}
<section class="home-brands-card" aria-label="Brands we install">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="home-brands-card__inner">
      <p class="home-brands-card__label">
        <span class="home-brands-card__count">{{ count(brand_strip_items()) }}</span>
        <span>brands, one installer — <a href="/brands">compare all</a></span>
      </p>
      <ul class="home-brands-card__grid">
        @foreach(brand_strip_items() as $item)
          <li class="home-brands-card__item">
            <a href="{{ $item['href'] }}" class="home-brands-card__link" title="{{ $item['alt'] }}">
              <x-img :src="$item['image']" preset="brand_grid" :alt="$item['alt']" loading="eager" class="home-brands-card__logo" />
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</section>
