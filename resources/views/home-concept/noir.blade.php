<!DOCTYPE html>
<html lang="en">
<head>
  @include('home-concept._head', [
    'fonts' => 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Inter:wght@300;400;500;600&display=swap',
    'themeColor' => '#0b0b0c',
  ])
</head>
<body class="nr">

<header class="nr-header" data-hc-header>
  <div class="nr-header__bar">
    <nav class="nr-nav nr-nav--left" aria-label="Primary">
      @foreach(array_slice($c['nav'], 0, 2, true) as $label => $menu)
        <div class="nr-nav__item"><a href="{{ $menu['href'] }}">{{ $label }}</a>
          <div class="nr-mega">@foreach($menu['cols'] as $colTitle => $links)<div><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>@endforeach</div>
        </div>
      @endforeach
    </nav>
    <a href="/" class="nr-wordmark" aria-label="Deluxe Windows — home">Deluxe <i>Windows</i></a>
    <nav class="nr-nav nr-nav--right" aria-label="Secondary">
      @foreach(array_slice($c['nav'], 2, 2, true) as $label => $menu)
        <div class="nr-nav__item"><a href="{{ $menu['href'] }}">{{ $label }}</a>
          <div class="nr-mega">@foreach($menu['cols'] as $colTitle => $links)<div><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>@endforeach</div>
        </div>
      @endforeach
      <div class="nr-nav__item"><a href="/special-offers" class="nr-nav__offer">Special Offers</a></div>
    </nav>
    <div class="nr-header__side">
      <a href="tel:{{ $c['phoneTel'] }}" class="nr-header__phone">{{ $c['phoneDisplay'] }}</a>
      <a href="#quote" class="nr-pill" data-hc-quote>Private estimate</a>
      <button type="button" class="nr-burger" data-hc-burger aria-expanded="false" aria-controls="nr-drawer" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
  </div>
  <div class="nr-drawer" id="nr-drawer" data-hc-drawer hidden>
    @foreach($c['nav'] as $label => $menu)
      <details><summary>{{ $label }}</summary>@foreach($menu['cols'] as $colTitle => $links)<p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>@endforeach</details>
    @endforeach
    <a href="/special-offers" class="nr-drawer__offer">Special Offers — {{ $c['discountLabel'] }}</a>
    <a href="tel:{{ $c['phoneTel'] }}" class="nr-pill nr-pill--block">Call {{ $c['phoneDisplay'] }}</a>
  </div>
</header>

<main>
<section class="nr-hero">
  <div class="nr-hero__bg"><x-img :src="$c['images']['hero']" preset="hero_bg" loading="eager" alt="" /></div>
  <div class="nr-hero__content" data-hc-reveal>
    <p class="nr-over">Window &amp; door atelier · Burlingame · since the 1990s</p>
    <h1 class="nr-h1">Light,<br><i>framed beautifully.</i></h1>
    <p class="nr-hero__sub">Every major brand. One employee-owned crew. Written quotes in 24 hours, most homes finished in a day or two — from $499 per window, installed.</p>
    <div class="nr-hero__cta">
      <a href="#quote" class="nr-btn" data-hc-quote>Request a private estimate</a>
      <a href="#collection" class="nr-ghost">View the collection</a>
    </div>
  </div>
  <ul class="nr-hero__facts">
    <li><b>{{ $c['yelpRating'] }}</b><span>Yelp · {{ $c['yelpCount'] }} reviews</span></li>
    <li><b>30<sup>+</sup></b><span>years in the Bay Area</span></li>
    <li><b>{{ $c['discountPercent'] }}<sup>%</sup></b><span>off through {{ $c['promoEnd'] ? $c['promoEnd']->format('M j') : 'season end' }}</span></li>
    <li><b>1–2</b><span>install days, typical home</span></li>
  </ul>
</section>

<section class="nr-sec nr-brands" id="brands">
  <p class="nr-over nr-center">Maison partners</p>
  <h2 class="nr-h2 nr-center">Eleven houses, <i>no house brand.</i></h2>
  <p class="nr-lede nr-center">An independent dealer owes you honesty, not volume. We recommend the window that suits the room — and tell you when the less expensive one is the better buy.</p>
  <ul class="nr-logos">
    @foreach($c['brands'] as $b)<li><a href="/brands/{{ $b['slug'] }}" title="{{ $b['name'] }}"><x-img :src="$b['image']" preset="brand_grid" loading="lazy" :alt="$b['name']" /></a></li>@endforeach
  </ul>
</section>

<section class="nr-sec" id="collection">
  <div class="nr-sec__head">
    <div><p class="nr-over">The collection</p><h2 class="nr-h2">Seven materials, <i>each with a reason.</i></h2></div>
    <p class="nr-lede">Installed prices with {{ $c['discountLabel'] }} applied. Hover to reveal.</p>
  </div>
  <div class="nr-collection">
    @foreach($c['materials'] as $i => $m)
      <a href="/windows/{{ $m['slug'] }}" class="nr-card" data-hc-reveal style="transition-delay: {{ $i * 60 }}ms">
        @if($m['image'] !== '')<x-img :src="$m['image']" preset="card" loading="lazy" :alt="$m['name']" />@else<div class="nr-card__blank"></div>@endif
        <span class="nr-card__tag">{{ $m['tag'] }}</span>
        <span class="nr-card__body">
          <span class="nr-card__name">{{ $m['short'] }}</span>
          <span class="nr-card__for">{{ $m['for'] }}</span>
          <span class="nr-card__price">from ${{ $m['from'] }} <s>${{ $m['was'] }}</s></span>
        </span>
      </a>
    @endforeach
    <a href="/windows" class="nr-card nr-card--more"><span>All window<br>types <i>→</i></span></a>
  </div>
</section>

<section class="nr-sec nr-pricing" id="pricing">
  <p class="nr-over nr-center">Transparent by design</p>
  <h2 class="nr-h2 nr-center">The price is the price.<br><i>Installed, all-in.</i></h2>
  <div class="nr-tiers">
    @foreach($c['tiers'] as $t)
      <div class="nr-tier"><h3>{{ $t['name'] }}</h3><p class="nr-tier__price"><s>${{ $t['was'] }}</s><b>${{ $t['from'] }}</b><span>per window, installed</span></p><p class="nr-tier__brands">{{ $t['brands'] }}</p><a href="#quote" class="nr-ghost" data-hc-quote>Hold this price</a></div>
    @endforeach
  </div>
  <ul class="nr-included">@foreach($c['included'] as $item)<li>{{ $item }}</li>@endforeach</ul>
  <p class="nr-center"><a href="/financing" class="nr-ghost">Monthly plans from $0 down</a></p>
</section>

<section class="nr-sec nr-doors" id="doors">
  <figure class="nr-doors__media"><x-img :src="$c['doors'][1]['image']" preset="cta" loading="lazy" alt="Wood clad multi-slide door" /><figcaption>Marvin Signature multi-slide, Hillsborough</figcaption></figure>
  <div class="nr-doors__text">
    <p class="nr-over">Doors</p>
    <h2 class="nr-h2">Where the room <i>steps outside.</i></h2>
    <p class="nr-lede">Entry, patio, multi-slide and pivot — installed by the same crew, filed under the same warranty, on the same day as your windows.</p>
    <ul class="nr-doorlist">
      @foreach($c['doors'] as $dr)<li><a href="/doors/{{ $dr['slug'] }}"><b>{{ $dr['name'] }}</b><span>{{ $dr['text'] }}</span><i>→</i></a></li>@endforeach
    </ul>
  </div>
</section>

<section class="nr-sec" id="process">
  <p class="nr-over nr-center">The process</p>
  <h2 class="nr-h2 nr-center">Four appointments. <i>Then quiet.</i></h2>
  <ol class="nr-steps">
    @foreach($c['steps'] as $i => [$t, $x])<li><span class="nr-steps__n">{{ $i + 1 }}</span><h3>{{ $t }}</h3><p>{{ $x }}</p></li>@endforeach
  </ol>
</section>

<section class="nr-sec nr-proof" id="proof">
  <div class="nr-sec__head">
    <div><p class="nr-over">Before · After</p><h2 class="nr-h2">One address, <i>one day apart.</i></h2></div>
    <p class="nr-lede">Single-pane aluminum from 1971, replaced with black aluminum-clad units and a fiberglass entry.</p>
  </div>
  @include('home-concept._compare', ['p' => 'nr'])
  <ul class="nr-stats">@foreach($c['stats'] as [$k, $v, $t])<li><b>{{ $v }}</b><span>{{ $k }}</span><small>{{ $t }}</small></li>@endforeach</ul>
  <p class="nr-fine nr-center">{{ $c['statsNote'] }}</p>
</section>

<section class="nr-sec nr-reviews" id="reviews">
  <p class="nr-over nr-center">Guest book</p>
  <h2 class="nr-h2 nr-center">{{ $c['yelpCount'] }} reviews. <i>{{ $c['yelpRating'] }} stars.</i></h2>
  <div class="nr-quotes">
    @forelse($c['reviews'] as $i => $r)
      <blockquote class="nr-quote {{ $i === 0 ? 'nr-quote--lead' : '' }}" data-hc-reveal>
        <p>{{ \Illuminate\Support\Str::limit(trim($r['text']), $i === 0 ? 420 : 200) }}</p>
        <footer><span class="nr-quote__ini">{{ $r['initials'] ?? mb_substr($r['author'], 0, 1) }}</span><span>{{ $r['author'] }}</span><span class="nr-quote__stars">{{ str_repeat('★', (int) $r['rating']) }}</span></footer>
      </blockquote>
    @empty
      <p class="nr-lede nr-center"><a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Read on Yelp</a></p>
    @endforelse
  </div>
  <p class="nr-center"><a href="{{ $c['yelpUrl'] }}" class="nr-ghost" target="_blank" rel="noopener noreferrer">All reviews on Yelp</a></p>
</section>

<section class="nr-sec nr-guarantee" id="guarantee">
  <p class="nr-over nr-center">Guarantee</p>
  <h2 class="nr-h2 nr-center">In writing. <i>Transferable.</i></h2>
  <div class="nr-gcards">@foreach($c['guarantee'] as [$big, $t, $x])<div class="nr-gcard"><b>{{ $big }}</b><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
  <p class="nr-fine nr-center">{{ $c['guaranteeNote'] }}</p>
</section>

<section class="nr-sec nr-area" id="area">
  <div class="nr-sec__head">
    <div><p class="nr-over">Certified · Local</p><h2 class="nr-h2">Nine counties. <i>One showroom.</i></h2></div>
    <p class="nr-lede">Factory-trained, AAMA-certified crews. Visit us at {{ $c['showroom'] }} — {{ $c['hours'] }}.</p>
  </div>
  <div class="nr-certs">@foreach($c['certs'] as $cert)<img loading="lazy" src="{{ $cert['image'] }}" alt="{{ $cert['name'] }}" />@endforeach<span>AAMA certified</span></div>
  <ul class="nr-counties">@foreach($c['counties'] as $n => $s)<li><a href="/county-hub-pages/{{ $s }}">{{ $n }}</a></li>@endforeach</ul>
</section>

<section class="nr-sec" id="pros">
  <p class="nr-over nr-center">For the trade</p>
  <h2 class="nr-h2 nr-center">Architects, builders, <i>portfolio owners.</i></h2>
  <div class="nr-pros">@foreach($c['pros'] as [$t, $x])<div><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
  <p class="nr-center"><a href="/contacts" class="nr-ghost">Commercial desk</a></p>
</section>

<section class="nr-sec nr-faq" id="faq">
  <p class="nr-over nr-center">Questions</p>
  <h2 class="nr-h2 nr-center">Asked <i>most often.</i></h2>
  <div class="nr-faqlist">@foreach($c['faqs'] as $i => [$q, $a])<details @if($i === 0) open @endif><summary>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach</div>
  <p class="nr-center"><a href="/faq" class="nr-ghost">Full FAQ</a></p>
</section>

<section class="nr-concierge" id="quote">
  <div class="nr-concierge__bg"><x-img :src="$c['images']['consult']" preset="cta" loading="lazy" alt="" /></div>
  <div class="nr-concierge__card">
    <p class="nr-over">Private estimate · {{ $c['discountLabel'] }}</p>
    <h2 class="nr-h2">Your dream home <i>starts here.</i></h2>
    <p class="nr-lede">Leave a name and number. A specialist calls within one business day, measures at your convenience, and sends an itemized quote within 24 hours.</p>
    @include('home-concept._form', ['p' => 'nr', 'submitLabel' => 'Request estimate'])
  </div>
</section>
</main>

<footer class="nr-footer">
  <a href="/" class="nr-wordmark nr-wordmark--lg">Deluxe <i>Windows</i></a>
  <ul class="nr-footer__links">@foreach($c['footerLinks'] as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>
  <p class="nr-footer__contact"><a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a> · {{ $c['showroom'] }} · {{ $c['hours'] }}</p>
  <p class="nr-footer__legal">©{{ date('Y') }} Deluxe Windows, Inc. · {{ $c['license'] }} · 100% employee-owned · All rights reserved.</p>
</footer>

<div class="nr-sticky"><a href="tel:{{ $c['phoneTel'] }}">Call</a><a href="#quote" data-hc-quote>Private estimate</a></div>

@include('home-concept._switcher')
@include('home-concept._scripts')
</body>
</html>
