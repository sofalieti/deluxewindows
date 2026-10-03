<!DOCTYPE html>
<html lang="en">
<head>
  @include('home-concept._head', [
    'fonts' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&family=DM+Sans:opsz,wght@9..40,400;9..40,500&display=swap',
    'themeColor' => '#f6f4ee',
  ])
</head>
<body class="nd">

<header class="nd-header" data-hc-header>
  <div class="nd-header__pill">
    <a href="/" class="nd-logo" aria-label="Deluxe Windows — home"><span class="nd-logo__dot"></span>Deluxe Windows</a>
    <nav class="nd-nav" aria-label="Primary">
      @foreach($c['nav'] as $label => $menu)
        <div class="nd-nav__item"><a href="{{ $menu['href'] }}">{{ $label }}</a>
          <div class="nd-mega">@foreach($menu['cols'] as $colTitle => $links)<div><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>@endforeach</div>
        </div>
      @endforeach
      <div class="nd-nav__item"><a href="/special-offers" class="nd-nav__offer">Special Offers</a></div>
    </nav>
    <div class="nd-header__right">
      <a href="tel:{{ $c['phoneTel'] }}" class="nd-header__phone">{{ $c['phoneDisplay'] }}</a>
      <a href="#quote" class="nd-btn nd-btn--sm" data-hc-quote>Free estimate</a>
      <button type="button" class="nd-burger" data-hc-burger aria-expanded="false" aria-controls="nd-drawer" aria-label="Menu"><span></span><span></span></button>
    </div>
  </div>
  <div class="nd-drawer" id="nd-drawer" data-hc-drawer hidden>
    @foreach($c['nav'] as $label => $menu)
      <details><summary>{{ $label }}</summary>@foreach($menu['cols'] as $colTitle => $links)<p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>@endforeach</details>
    @endforeach
    <a href="/special-offers" class="nd-drawer__offer">Special Offers · {{ $c['discountLabel'] }}</a>
    <a href="tel:{{ $c['phoneTel'] }}" class="nd-btn nd-btn--block">Call {{ $c['phoneDisplay'] }}</a>
  </div>
</header>

<main>

<section class="nd-hero">
  <div class="nd-wrap nd-hero__grid">
    <div class="nd-hero__copy" data-hc-reveal>
      <span class="nd-chip"><i></i>{{ $c['discountLabel'] }} · ends {{ $c['promoEnd'] ? $c['promoEnd']->format('M j') : 'soon' }}</span>
      <h1 class="nd-h1">A calmer, brighter home starts at the window.</h1>
      <p class="nd-lede">Replacement windows and doors for the Bay Area, installed in a day or two by our own employee-owners. Eleven brands, honest advice, written quotes — from $499 per window.</p>
      <div class="nd-hero__cta"><a href="#quote" class="nd-btn" data-hc-quote>Get my free estimate</a><a href="#windows" class="nd-btn nd-btn--ghost">See windows &amp; prices</a></div>
      <ul class="nd-hero__trust">
        <li><b>{{ $c['yelpRating'] }}★</b><span>{{ $c['yelpCount'] }} Yelp reviews</span></li>
        <li><b>30+</b><span>years in the Bay</span></li>
        <li><b>100%</b><span>employee-owned</span></li>
      </ul>
    </div>
    <div class="nd-hero__art">
      <figure class="nd-hero__main"><x-img :src="$c['images']['hero']" preset="hero_bg" loading="eager" alt="Bright living room with new windows" /></figure>
      <figure class="nd-hero__small nd-hero__small--a"><x-img :src="$c['images']['samples']" preset="card" loading="lazy" alt="Window corner samples" /><figcaption>Real corner samples at your consultation</figcaption></figure>
      <div class="nd-hero__small nd-hero__small--b"><b>1–2 days</b><span>typical install</span></div>
    </div>
  </div>
</section>

<section class="nd-sec nd-sec--tight" id="brands">
  <div class="nd-wrap">
    <div class="nd-brands" data-hc-reveal>
      <p class="nd-brands__lead">Independent dealer for <b>11 brands</b> — we recommend what fits your home, not a house label.</p>
      <ul class="nd-brands__list">@foreach($c['brands'] as $b)<li><a href="/brands/{{ $b['slug'] }}" title="{{ $b['name'] }}"><x-img :src="$b['image']" preset="brand_grid" loading="lazy" :alt="$b['name']" /></a></li>@endforeach<li><a href="/brands" class="nd-brands__all">All brands →</a></li></ul>
    </div>
  </div>
</section>

<section class="nd-sec" id="windows">
  <div class="nd-wrap">
    <header class="nd-sec__head" data-hc-reveal><span class="nd-eyebrow">Windows</span><h2 class="nd-h2">Pick a material. We'll handle the rest.</h2><p class="nd-lede">Prices per window, installed, with {{ $c['discountLabel'] }} applied. Tap a material to see who it's for.</p></header>
    <div class="nd-tabs" data-hc-tabs>
      <div class="nd-tabs__list" role="tablist">
        @foreach($c['materials'] as $m)<button type="button" class="nd-tab" role="tab" data-hc-tab="{{ $m['slug'] }}">{{ $m['short'] }}<small>from ${{ $m['from'] }}</small></button>@endforeach
      </div>
      @foreach($c['materials'] as $i => $m)
        <div class="nd-panel" data-hc-panel="{{ $m['slug'] }}" hidden>
          <div class="nd-panel__media">
            @if(!empty($m['image']))<x-img :src="$m['image']" preset="card" loading="lazy" :alt="$m['name']" />@else<div class="nd-panel__blank">{{ $m['short'] }}</div>@endif
          </div>
          <div class="nd-panel__body">
            <span class="nd-chip nd-chip--soft">{{ $m['tag'] }}</span>
            <h3>{{ $m['name'] }}</h3>
            <p class="nd-panel__for"><b>Best for:</b> {{ $m['for'] }}</p>
            <p class="nd-panel__note"><b>Why people pick it:</b> {{ $m['note'] }}</p>
            <div class="nd-panel__price"><b>${{ $m['from'] }}</b><s>${{ $m['was'] }}</s><span>per window · installed</span></div>
            <div class="nd-panel__cta"><a href="/windows/{{ $m['slug'] }}" class="nd-btn nd-btn--ghost">About {{ $m['short'] }} windows</a><a href="#quote" class="nd-btn" data-hc-quote>Price my windows</a></div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

<section class="nd-sec nd-sec--sage" id="pricing">
  <div class="nd-wrap">
    <header class="nd-sec__head nd-sec__head--center" data-hc-reveal><span class="nd-eyebrow">Pricing</span><h2 class="nd-h2">Simple tiers. Nothing hidden.</h2><p class="nd-lede">Every price includes removal, disposal, Low-E glass, trim and clean-up. Financing from $0 down.</p></header>
    <div class="nd-tiers">
      @foreach($c['tiers'] as $j => $t)
        <div class="nd-tier @if($j === 1) nd-tier--hi @endif" data-hc-reveal>
          @if($j === 1)<span class="nd-tier__badge">Most chosen</span>@endif
          <h3>{{ $t['name'] }}</h3>
          <div class="nd-tier__price"><s>${{ $t['was'] }}</s><b>${{ $t['from'] }}</b><span>per window installed</span></div>
          <p>{{ $t['brands'] }}</p>
          <a href="#quote" class="nd-btn @if($j !== 1) nd-btn--ghost @endif nd-btn--block" data-hc-quote>Get this price</a>
        </div>
      @endforeach
    </div>
    <ul class="nd-included" data-hc-reveal>@foreach($c['included'] as $item)<li>{{ $item }}</li>@endforeach</ul>
    <p class="nd-center"><a href="/financing" class="nd-link">Explore financing options →</a></p>
  </div>
</section>

<section class="nd-sec" id="doors">
  <div class="nd-wrap">
    <header class="nd-sec__head" data-hc-reveal><span class="nd-eyebrow">Doors</span><h2 class="nd-h2">Open the house up.</h2><p class="nd-lede">Entry, patio, multi-slide, bifold and pivot doors — installed by the same crew, often on the same visit as your windows.</p></header>
    <div class="nd-doors">
      @foreach($c['doors'] as $dr)
        <a href="/doors/{{ $dr['slug'] }}" class="nd-door" data-hc-reveal>
          <div class="nd-door__media">@if($dr['image'] !== '')<x-img :src="$dr['image']" preset="card" loading="lazy" :alt="$dr['name'].' doors'" />@else<span>{{ $dr['name'] }}</span>@endif</div>
          <b>{{ $dr['name'] }} doors</b><p>{{ $dr['text'] }}</p><span class="nd-door__arrow">→</span>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="nd-sec nd-sec--sand" id="process">
  <div class="nd-wrap nd-process">
    <div class="nd-process__text" data-hc-reveal>
      <span class="nd-eyebrow">How it works</span>
      <h2 class="nd-h2">Four steps, two of them ours.</h2>
      <p class="nd-lede">You pick the windows and the day. We measure, order, install and clean up — and we don't leave until every sash works smoothly.</p>
      <figure class="nd-process__img"><x-img :src="$c['images']['installer']" preset="card" loading="lazy" alt="Deluxe Windows installer fitting a window" /></figure>
    </div>
    <ol class="nd-steps">
      @foreach($c['steps'] as $i => [$t, $x])<li data-hc-reveal><span class="nd-steps__n">{{ $i + 1 }}</span><div><h3>{{ $t }}</h3><p>{{ $x }}</p></div></li>@endforeach
    </ol>
  </div>
</section>

<section class="nd-sec" id="proof">
  <div class="nd-wrap">
    <header class="nd-sec__head nd-sec__head--center" data-hc-reveal><span class="nd-eyebrow">Before &amp; after</span><h2 class="nd-h2">One day. Whole new feeling.</h2><p class="nd-lede">1971 single-pane aluminum replaced with black-clad units and a fiberglass entry door. Slide to compare.</p></header>
    <div class="nd-compare-card" data-hc-reveal>@include('home-concept._compare', ['p' => 'nd'])</div>
    <ul class="nd-stats">@foreach($c['stats'] as [$k, $v, $t])<li data-hc-reveal><b>{{ $v }}</b><span>{{ $k }}</span><small>{{ $t }}</small></li>@endforeach</ul>
    <p class="nd-fine nd-center">{{ $c['statsNote'] }}</p>
  </div>
</section>

<section class="nd-sec nd-sec--sage" id="reviews">
  <div class="nd-wrap">
    <header class="nd-sec__head nd-sec__head--center" data-hc-reveal><span class="nd-eyebrow">Reviews</span><h2 class="nd-h2">{{ $c['yelpRating'] }} stars from {{ $c['yelpCount'] }} neighbors.</h2><p class="nd-lede">Pulled straight from <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>, unedited.</p></header>
    <div class="nd-reviews">
      @forelse($c['reviews'] as $r)
        <blockquote class="nd-review" data-hc-reveal><span class="nd-review__stars">{{ str_repeat('★', (int) $r['rating']) }}</span><p>{{ \Illuminate\Support\Str::limit(trim($r['text']), 230) }}</p><footer><span class="nd-review__av">{{ mb_substr($r['author'], 0, 1) }}</span><b>{{ $r['author'] }}</b>@if(!empty($r['published_label']))<small>{{ $r['published_label'] }}</small>@endif</footer></blockquote>
      @empty
        <p class="nd-center">Read reviews on <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>.</p>
      @endforelse
    </div>
    <p class="nd-center"><a href="/testimonials" class="nd-btn nd-btn--ghost">More testimonials</a></p>
  </div>
</section>

<section class="nd-sec" id="guarantee">
  <div class="nd-wrap">
    <header class="nd-sec__head" data-hc-reveal><span class="nd-eyebrow">Guarantee</span><h2 class="nd-h2">Peace of mind, in writing.</h2><p class="nd-lede">{{ $c['guaranteeNote'] }}</p></header>
    <div class="nd-gcards">@foreach($c['guarantee'] as [$big, $t, $x])<div class="nd-gcard" data-hc-reveal><b>{{ $big }}</b><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
  </div>
</section>

<section class="nd-sec nd-sec--sand" id="area">
  <div class="nd-wrap nd-area">
    <div data-hc-reveal>
      <span class="nd-eyebrow">Certifications &amp; service area</span>
      <h2 class="nd-h2">Certified crews. Nine counties.</h2>
      <p class="nd-lede">AAMA-certified installers, factory-trained by the brands we carry. Visit the showroom at {{ $c['showroom'] }} ({{ $c['hours'] }}) or we come to you.</p>
      <div class="nd-certs">@foreach($c['certs'] as $cert)<img loading="lazy" src="{{ $cert['image'] }}" alt="{{ $cert['name'] }}" />@endforeach<span class="nd-chip nd-chip--soft">AAMA certified</span></div>
    </div>
    <ul class="nd-counties" data-hc-reveal>@foreach($c['counties'] as $n => $s)<li><a href="/county-hub-pages/{{ $s }}">{{ $n }}<small>County</small></a></li>@endforeach</ul>
  </div>
</section>

<section class="nd-sec" id="pros">
  <div class="nd-wrap">
    <header class="nd-sec__head nd-sec__head--center" data-hc-reveal><span class="nd-eyebrow">For professionals</span><h2 class="nd-h2">Built for the trade, too.</h2></header>
    <div class="nd-pros">@foreach($c['pros'] as [$t, $x])<div class="nd-pro" data-hc-reveal><h3>{{ $t }}</h3><p>{{ $x }}</p><a href="/contacts" class="nd-link">Talk to our commercial desk →</a></div>@endforeach</div>
  </div>
</section>

<section class="nd-sec nd-sec--sage" id="faq">
  <div class="nd-wrap nd-faqwrap">
    <header class="nd-sec__head" data-hc-reveal><span class="nd-eyebrow">FAQ</span><h2 class="nd-h2">Good questions.</h2><p class="nd-lede">The five we hear most. <a href="/faq">All answers →</a></p></header>
    <div class="nd-faq">@foreach($c['faqs'] as $i => [$q, $a])<details @if($i === 0) open @endif data-hc-reveal><summary>{{ $q }}<i></i></summary><p>{{ $a }}</p></details>@endforeach</div>
  </div>
</section>

<section class="nd-cta" id="quote">
  <div class="nd-wrap nd-cta__card">
    <div class="nd-cta__text" data-hc-reveal>
      <span class="nd-eyebrow">Free in-home estimate</span>
      <h2 class="nd-h2">Your dream home starts here.</h2>
      <p class="nd-lede">Tell us your name and number. A specialist calls within one business day, visits when it suits you, and sends an itemized quote within 24 hours.</p>
      <ul class="nd-cta__list"><li>No commission pressure</li><li>Quote locked for 30 days</li><li>{{ $c['discountLabel'] }} applied automatically</li></ul>
      <p class="nd-cta__phone">Prefer to talk? <a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a></p>
    </div>
    <div class="nd-cta__form" data-hc-reveal>
      @include('home-concept._form', ['p' => 'nd', 'submitLabel' => 'Request my free estimate'])
    </div>
  </div>
</section>
</main>

<footer class="nd-footer">
  <div class="nd-wrap nd-footer__grid">
    <div class="nd-footer__brand"><a href="/" class="nd-logo"><span class="nd-logo__dot"></span>Deluxe Windows</a><p>Windows &amp; doors, installed with care across the San Francisco Bay Area since 1996.</p><p class="nd-fine">{{ $c['license'] }} · 100% employee-owned</p></div>
    <ul class="nd-footer__links">@foreach($c['footerLinks'] as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>
    <div class="nd-footer__contact"><a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a><p>{{ $c['showroom'] }}</p><p>{{ $c['hours'] }}</p></div>
  </div>
  <p class="nd-footer__legal">©{{ date('Y') }} Deluxe Windows, Inc. All rights reserved.</p>
</footer>

<div class="nd-sticky"><a href="tel:{{ $c['phoneTel'] }}">Call</a><a href="#quote" data-hc-quote>Free estimate</a></div>

@include('home-concept._switcher')
@include('home-concept._scripts')
</body>
</html>
