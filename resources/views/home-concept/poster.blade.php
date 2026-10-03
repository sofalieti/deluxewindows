<!DOCTYPE html>
<html lang="en">
<head>
  @include('home-concept._head', [
    'fonts' => 'https://fonts.googleapis.com/css2?family=Unbounded:wght@500;700;900&family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,700&display=swap',
    'themeColor' => '#fff4e6',
  ])
</head>
<body class="po">

<div class="po-marquee" aria-hidden="true"><div class="po-marquee__track">@for($i = 0; $i < 2; $i++)<span>{{ $c['discountLabel'] }} on windows &amp; doors</span><span>★</span><span>Vinyl from $499 installed</span><span>★</span><span>{{ $c['yelpRating'] }} on Yelp · {{ $c['yelpCount'] }} reviews</span><span>★</span><span>100% employee-owned</span><span>★</span><span>Installed in 1–2 days</span><span>★</span>@endfor</div></div>

<header class="po-header" data-hc-header>
  <div class="po-header__in">
    <a href="/" class="po-logo" aria-label="Deluxe Windows — home">DELUXE<br>WINDOWS</a>
    <nav class="po-nav" aria-label="Primary">
      @foreach($c['nav'] as $label => $menu)
        <div class="po-nav__item"><a href="{{ $menu['href'] }}">{{ $label }}</a>
          <div class="po-mega">@foreach($menu['cols'] as $colTitle => $links)<div><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>@endforeach</div>
        </div>
      @endforeach
      <div class="po-nav__item"><a href="/special-offers" class="po-nav__offer">Special Offers <i>{{ $c['discountLabel'] }}</i></a></div>
    </nav>
    <a href="tel:{{ $c['phoneTel'] }}" class="po-header__phone">{{ $c['phoneDisplay'] }}</a>
    <a href="#quote" class="po-btn po-btn--sm" data-hc-quote>Get quote</a>
    <button type="button" class="po-burger" data-hc-burger aria-expanded="false" aria-controls="po-drawer" aria-label="Menu"><span></span><span></span><span></span></button>
  </div>
  <div class="po-drawer" id="po-drawer" data-hc-drawer hidden>
    @foreach($c['nav'] as $label => $menu)
      <details><summary>{{ $label }}</summary>@foreach($menu['cols'] as $colTitle => $links)<p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>@endforeach</details>
    @endforeach
    <a href="/special-offers" class="po-drawer__offer">Special Offers · {{ $c['discountLabel'] }}</a>
    <a href="tel:{{ $c['phoneTel'] }}" class="po-btn po-btn--block">Call {{ $c['phoneDisplay'] }}</a>
  </div>
</header>

<main class="po-main">

<section class="po-bento po-hero" aria-label="Hero">
  <div class="po-tile po-tile--headline po-tile--cream">
    <span class="po-sticker po-sticker--sun">{{ $c['discountLabel'] }}<small>until {{ $c['promoEnd'] ? $c['promoEnd']->format('M j') : 'TBA' }}</small></span>
    <h1 class="po-h1">NEW<br>WINDOWS.<br><span>BIG</span> MOOD.</h1>
    <p class="po-lede">Bay Area replacement windows &amp; doors, installed in a day or two by people who own the company. 11 brands. Prices on the tin.</p>
    <div class="po-hero__cta"><a href="#quote" class="po-btn po-btn--lg" data-hc-quote>Get my quote →</a><a href="#windows" class="po-btn po-btn--lg po-btn--outline">See prices</a></div>
  </div>
  <div class="po-tile po-tile--photo po-tile--hero-photo"><x-img :src="$c['images']['hero']" preset="hero_bg" loading="eager" alt="Bay Area home with new windows" /><span class="po-sticker po-sticker--white po-sticker--corner">San Mateo County · Marvin Elevate</span></div>
  <div class="po-tile po-tile--coral po-tile--stat"><b>$499</b><span>per vinyl window, installed</span></div>
  <div class="po-tile po-tile--lemon po-tile--stat"><b>{{ $c['yelpRating'] }}★</b><span>{{ $c['yelpCount'] }} Yelp reviews</span></div>
  <div class="po-tile po-tile--ink po-tile--stat"><b>1–2</b><span>days, typical install</span></div>
  <div class="po-tile po-tile--lilac po-tile--stat"><b>100%</b><span>employee-owned since day one</span></div>
</section>

<section class="po-sec" id="brands">
  <div class="po-sec__head"><h2 class="po-h2">11 brands.<br>Zero favorites.</h2><p class="po-lede">We're an independent dealer. The pitch is about your house and your budget, not a quota.</p></div>
  <ul class="po-logos">@foreach($c['brands'] as $i => $b)<li style="--rot: {{ ($i % 2 ? 1 : -1) * (1 + $i % 3) * .6 }}deg"><a href="/brands/{{ $b['slug'] }}" title="{{ $b['name'] }}"><x-img :src="$b['image']" preset="brand_grid" loading="lazy" :alt="$b['name']" /></a></li>@endforeach<li class="po-logos__all"><a href="/brands">ALL BRANDS →</a></li></ul>
</section>

<section class="po-sec" id="windows">
  <div class="po-sec__head"><h2 class="po-h2">Pick your<br>material.</h2><p class="po-lede">Per window, installed, {{ $c['discountLabel'] }} already in. Tap a tile.</p></div>
  <div class="po-bento po-materials">
    @foreach($c['materials'] as $i => $m)
      @php($tone = ['cream','coral','lemon','lilac','mint','ink','cream'][$i % 7])
      <a href="/windows/{{ $m['slug'] }}" class="po-tile po-tile--{{ $tone }} po-mat @if($i === 0) po-mat--big @endif">
        <span class="po-sticker po-sticker--white">{{ $m['tag'] }}</span>
        @if($i === 0 && !empty($m['image']))<x-img :src="$m['image']" preset="card" loading="lazy" :alt="$m['name']" class="po-mat__img" />@endif
        <b class="po-mat__name">{{ strtoupper($m['short']) }}</b>
        <span class="po-mat__for">{{ $m['for'] }}</span>
        <span class="po-mat__price">${{ $m['from'] }}<s>${{ $m['was'] }}</s></span>
      </a>
    @endforeach
    <a href="/windows" class="po-tile po-tile--outline po-mat po-mat--more"><b>ALL<br>WINDOW<br>TYPES →</b></a>
  </div>
</section>

<section class="po-sec po-sec--ink" id="pricing">
  <div class="po-sec__head"><h2 class="po-h2">Three prices.<br>No asterisks.</h2><p class="po-lede">Every tier includes the whole job. Financing from $0 down.</p></div>
  <div class="po-tiers">
    @foreach($c['tiers'] as $j => $t)
      @php($tone = ['lemon','coral','lilac'][$j])
      <div class="po-tile po-tile--{{ $tone }} po-tier"><span class="po-tier__n">0{{ $j + 1 }}</span><h3>{{ $t['name'] }}</h3><div class="po-tier__price"><b>${{ $t['from'] }}</b><s>${{ $t['was'] }}</s></div><span class="po-tier__per">per window · installed</span><p>{{ $t['brands'] }}</p><a href="#quote" class="po-btn po-btn--block" data-hc-quote>Lock it in →</a></div>
    @endforeach
  </div>
  <ul class="po-included">@foreach($c['included'] as $item)<li>{{ $item }}</li>@endforeach</ul>
  <p class="po-center"><a href="/financing" class="po-link">Financing options →</a></p>
</section>

<section class="po-sec" id="doors">
  <div class="po-sec__head"><h2 class="po-h2">Doors,<br>same crew.</h2><p class="po-lede">Entry, patio, multi-slide, bifold, pivot. Bundle with windows and labor gets cheaper.</p></div>
  <div class="po-rail-wrap">
    <div class="po-rail" data-hc-rail="doors">
      @foreach($c['doors'] as $i => $dr)
        @php($tone = ['mint','coral','lemon','lilac','cream','ink'][$i % 6])
        <a href="/doors/{{ $dr['slug'] }}" class="po-tile po-tile--{{ $tone }} po-doorcard">
          @if($dr['image'] !== '')<x-img :src="$dr['image']" preset="card" loading="lazy" :alt="$dr['name'].' doors'" />@else<div class="po-doorcard__blank">{{ strtoupper($dr['name']) }}</div>@endif
          <b>{{ $dr['name'] }}</b><p>{{ $dr['text'] }}</p>
        </a>
      @endforeach
    </div>
    <div class="po-rail__nav"><button type="button" data-hc-rail-prev="doors" aria-label="Previous">←</button><button type="button" data-hc-rail-next="doors" aria-label="Next">→</button><a href="/doors" class="po-link">All doors →</a></div>
  </div>
</section>

<section class="po-sec" id="process">
  <div class="po-sec__head"><h2 class="po-h2">How it<br>goes down.</h2><p class="po-lede">Four steps. You handle two of them (and one is just saying yes).</p></div>
  <ol class="po-steps">
    @foreach($c['steps'] as $i => [$t, $x])
      @php($tone = ['lemon','coral','lilac','mint'][$i])
      <li class="po-tile po-tile--{{ $tone }}"><span class="po-steps__n">{{ $i + 1 }}</span><h3>{{ $t }}</h3><p>{{ $x }}</p></li>
    @endforeach
  </ol>
</section>

<section class="po-sec" id="proof">
  <div class="po-sec__head"><h2 class="po-h2">Before →<br>after.</h2><p class="po-lede">1971 aluminum single-pane out, black clad in. One day on site. Drag it.</p></div>
  <div class="po-tile po-tile--outline po-compare-tile">@include('home-concept._compare', ['p' => 'po', 'labelBefore' => 'BEFORE', 'labelAfter' => 'AFTER'])</div>
  <ul class="po-stats">@foreach($c['stats'] as $i => [$k, $v, $t])@php($tone = ['coral','mint','lemon'][$i])<li class="po-tile po-tile--{{ $tone }}"><b>{{ $v }}</b><span>{{ $k }}</span><small>{{ $t }}</small></li>@endforeach</ul>
  <p class="po-fine">{{ $c['statsNote'] }}</p>
</section>

<section class="po-sec po-sec--lemon" id="reviews">
  <div class="po-sec__head"><h2 class="po-h2">{{ $c['yelpCount'] }} people<br>said nice things.</h2><p class="po-lede">Live from <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>. {{ $c['yelpRating'] }} average. Unedited.</p></div>
  <div class="po-reviews">
    @forelse($c['reviews'] as $i => $r)
      <blockquote class="po-tile po-tile--cream po-review" style="--rot: {{ ($i % 2 ? 1 : -1) * (0.5 + ($i % 3) * .4) }}deg"><span class="po-review__stars">{{ str_repeat('★', (int) $r['rating']) }}</span><p>{{ \Illuminate\Support\Str::limit(trim($r['text']), 220) }}</p><footer>— {{ $r['author'] }}@if(!empty($r['published_label'])) <small>{{ $r['published_label'] }}</small>@endif</footer></blockquote>
    @empty
      <p>Read reviews on <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>.</p>
    @endforelse
  </div>
  <p class="po-center"><a href="/testimonials" class="po-btn po-btn--outline">More testimonials</a></p>
</section>

<section class="po-sec" id="guarantee">
  <div class="po-sec__head"><h2 class="po-h2">Guaranteed.<br>In writing.</h2><p class="po-lede">{{ $c['guaranteeNote'] }}</p></div>
  <div class="po-gcards">@foreach($c['guarantee'] as $i => [$big, $t, $x])@php($tone = ['ink','coral','lilac'][$i])<div class="po-tile po-tile--{{ $tone }} po-gcard"><b>{{ strtoupper($big) }}</b><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
</section>

<section class="po-sec" id="area">
  <div class="po-sec__head"><h2 class="po-h2">Certified.<br>Nine counties.</h2><p class="po-lede">AAMA-certified, factory-trained crews. Showroom: {{ $c['showroom'] }} · {{ $c['hours'] }}.</p></div>
  <div class="po-certs po-tile po-tile--outline">@foreach($c['certs'] as $cert)<img loading="lazy" src="{{ $cert['image'] }}" alt="{{ $cert['name'] }}" />@endforeach<span class="po-sticker po-sticker--sun">AAMA certified</span></div>
  <ul class="po-counties">@foreach($c['counties'] as $n => $s)@php($tone = ['coral','lemon','lilac','mint','cream'][$loop->index % 5])<li><a href="/county-hub-pages/{{ $s }}" class="po-tile po-tile--{{ $tone }}">{{ strtoupper($n) }}</a></li>@endforeach</ul>
</section>

<section class="po-sec" id="pros">
  <div class="po-sec__head"><h2 class="po-h2">For the<br>trade.</h2></div>
  <div class="po-pros">@foreach($c['pros'] as $i => [$t, $x])<div class="po-tile po-tile--outline po-pro"><span class="po-pro__n">0{{ $i + 1 }}</span><h3>{{ $t }}</h3><p>{{ $x }}</p><a href="/contacts" class="po-link">Commercial desk →</a></div>@endforeach</div>
</section>

<section class="po-sec" id="faq">
  <div class="po-sec__head"><h2 class="po-h2">FAQ.</h2><p class="po-lede">Five quick ones. <a href="/faq">The whole list →</a></p></div>
  <div class="po-faq">@foreach($c['faqs'] as $i => [$q, $a])<details class="po-tile po-tile--cream" @if($i === 0) open @endif><summary>{{ $q }}<i>+</i></summary><p>{{ $a }}</p></details>@endforeach</div>
</section>

<section class="po-cta" id="quote">
  <div class="po-bento po-cta__grid">
    <div class="po-tile po-tile--coral po-cta__text">
      <span class="po-sticker po-sticker--white">Free estimate</span>
      <h2 class="po-h2">YOUR DREAM HOME<br>STARTS <span>HERE.</span></h2>
      <p class="po-lede">Name + phone. A real person calls within one business day, measures, and sends an itemized quote in 24 h. {{ $c['discountLabel'] }} applied.</p>
      <a href="tel:{{ $c['phoneTel'] }}" class="po-cta__phone">{{ $c['phoneDisplay'] }}</a>
    </div>
    <div class="po-tile po-tile--cream po-cta__form">
      @include('home-concept._form', ['p' => 'po', 'submitLabel' => 'Send it →'])
    </div>
  </div>
</section>
</main>

<footer class="po-footer">
  <div class="po-footer__grid">
    <div><a href="/" class="po-logo po-logo--lg">DELUXE<br>WINDOWS</a><p class="po-footer__tag">Windows &amp; doors for the Bay Area. {{ $c['license'] }}. 100% employee-owned.</p></div>
    <ul class="po-footer__links">@foreach($c['footerLinks'] as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>
    <div class="po-footer__contact"><a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a><p>{{ $c['showroom'] }}</p><p>{{ $c['hours'] }}</p></div>
  </div>
  <p class="po-footer__legal">©{{ date('Y') }} Deluxe Windows, Inc. All rights reserved.</p>
</footer>

<div class="po-sticky"><a href="tel:{{ $c['phoneTel'] }}">Call</a><a href="#quote" data-hc-quote>Get quote →</a></div>

@include('home-concept._switcher')
@include('home-concept._scripts')
</body>
</html>
