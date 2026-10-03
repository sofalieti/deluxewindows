{{--
  Home hero brands row — variant "new" (?new-brands=true).
  Rendered inside the hero, pinned to its top edge; logos are knocked out to
  white over the hero photo/video and stay visible on every breakpoint.
--}}
<div class="hero-brands" aria-label="Brands we install">
  <div class="w-layout-blockcontainer container-default w-container">
    <ul class="hero-brands__list">
      @foreach(brand_strip_items() as $item)
        <li class="hero-brands__item">
          <a href="{{ $item['href'] }}" class="hero-brands__link" title="{{ $item['alt'] }}">
            <x-img :src="$item['image']" preset="brand_grid" :alt="$item['alt']" loading="eager" class="hero-brands__logo" />
          </a>
        </li>
      @endforeach
    </ul>
  </div>
</div>
