<!DOCTYPE html>
<html lang="en">
<head>
  @include('home-concept._head', [
    'fonts' => 'https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@75..125,400..900&family=JetBrains+Mono:wght@400;500&display=swap',
    'themeColor' => '#ffffff',
  ])
</head>
<body class="sw">

<header class="sw-header" data-hc-header>
  <div class="sw-header__row">
    <a href="/" class="sw-mark" aria-label="Deluxe Windows — home"><b>DW</b><span>Deluxe Windows<br>Burlingame, CA</span></a>
    <nav class="sw-nav" aria-label="Primary">
      @foreach($c['nav'] as $label => $menu)
        <div class="sw-nav__cell"><a href="{{ $menu['href'] }}">{{ $label }}</a>
          <div class="sw-mega">@foreach($menu['cols'] as $colTitle => $links)<div><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>@endforeach</div>
        </div>
      @endforeach
      <div class="sw-nav__cell sw-nav__cell--offer"><a href="/special-offers">Special Offers</a></div>
    </nav>
    <a href="tel:{{ $c['phoneTel'] }}" class="sw-header__phone">{{ $c['phoneDisplay'] }}</a>
    <a href="#quote" class="sw-header__cta" data-hc-quote>Get quote →</a>
    <button type="button" class="sw-burger" data-hc-burger aria-expanded="false" aria-controls="sw-drawer" aria-label="Menu">Menu</button>
  </div>
  <div class="sw-drawer" id="sw-drawer" data-hc-drawer hidden>
    @foreach($c['nav'] as $label => $menu)
      <details><summary>{{ $label }}</summary>@foreach($menu['cols'] as $colTitle => $links)<p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>@endforeach</details>
    @endforeach
    <a href="/special-offers" class="sw-drawer__offer">Special Offers — {{ $c['discountLabel'] }}</a>
    <a href="tel:{{ $c['phoneTel'] }}" class="sw-drawer__call">Call {{ $c['phoneDisplay'] }}</a>
  </div>
</header>

<main class="sw-main">

<section class="sw-hero">
  <div class="sw-hero__copy">
    <p class="sw-mono">Replacement windows &amp; doors — San Francisco Bay Area — {{ $c['discountLabel'] }} until {{ $c['promoEnd'] ? $c['promoEnd']->format('Y-m-d') : 'TBA' }}</p>
    <h1 class="sw-h1">Replacement windows.<br>Priced like a spreadsheet,<br><span>installed like a promise.</span></h1>
    <p class="sw-lede">Independent dealer for 11 brands. 100% employee-owned. Written, itemized quotes in 24 h. Most homes done in 1–2 days. From <b>$499 / window</b>, installed.</p>
    <div class="sw-hero__actions"><a href="#quote" class="sw-btn sw-btn--red" data-hc-quote>Get my quote →</a><a href="#windows" class="sw-btn">Price table ↓</a></div>
  </div>
  <table class="sw-spec" aria-label="Key facts">
    <tbody>
      <tr><th>Yelp rating</th><td>{{ $c['yelpRating'] }} / 5 <small>({{ $c['yelpCount'] }})</small></td></tr>
      <tr><th>Years</th><td>30+</td></tr>
      <tr><th>Ownership</th><td>100% employee</td></tr>
      <tr><th>Install time</th><td>1–2 days</td></tr>
      <tr><th>License</th><td>{{ $c['license'] }}</td></tr>
      <tr><th>Starting price</th><td>$499 <small>installed</small></td></tr>
    </tbody>
  </table>
  <figure class="sw-hero__media"><x-img :src="$c['images']['hero']" preset="hero_bg" loading="eager" alt="Bay Area home after window replacement" /><figcaption class="sw-mono">Fig. 01 — Craftsman, San Mateo County. Marvin Elevate, black exterior.</figcaption></figure>
</section>

<section class="sw-grid sw-trust" aria-label="Trust">
  @foreach([['AAMA','certified installers'],['100%','employee-owned'],[$c['yelpRating'].'★',$c['yelpCount'].' Yelp reviews'],['$0','down financing'],['11','brands, one dealer'],['24 h','written quote']] as [$v, $k])
    <div class="sw-cell"><b>{{ $v }}</b><span class="sw-mono">{{ $k }}</span></div>
  @endforeach
</section>

<section class="sw-sec" id="brands">
  <header class="sw-sec__head"><span class="sw-mono">01 / Brands</span><h2 class="sw-h2">Eleven brands. No house brand.</h2><p class="sw-lede">We are not paid to push one label. The recommendation is about your house, your climate and your budget — and sometimes the cheaper window wins.</p></header>
  <ul class="sw-logos">
    @foreach($c['brands'] as $i => $b)<li><a href="/brands/{{ $b['slug'] }}"><span class="sw-mono">{{ sprintf('%02d', $i + 1) }}</span><x-img :src="$b['image']" preset="brand_grid" loading="lazy" :alt="$b['name']" /></a></li>@endforeach
    <li class="sw-logos__more"><a href="/brands"><span class="sw-mono">12</span><b>All brands →</b></a></li>
  </ul>
</section>

<section class="sw-sec" id="windows">
  <header class="sw-sec__head"><span class="sw-mono">02 / Windows</span><h2 class="sw-h2">Price table, by material.</h2><p class="sw-lede">Per window, installed. {{ $c['discountLabel'] }} applied. Click a row for brands, glass packages and hardware.</p></header>
  <div class="sw-tablewrap">
    <table class="sw-table">
      <thead><tr><th class="sw-mono">#</th><th>Material</th><th>Best for</th><th>Why it wins</th><th>Tag</th><th class="sw-r">Regular</th><th class="sw-r">Now</th><th></th></tr></thead>
      <tbody>
        @foreach($c['materials'] as $i => $m)
          <tr onclick="window.location='/windows/{{ $m['slug'] }}'" tabindex="0">
            <td class="sw-mono">{{ sprintf('%02d', $i + 1) }}</td>
            <td class="sw-table__name"><a href="/windows/{{ $m['slug'] }}">{{ $m['short'] }}</a></td>
            <td>{{ $m['for'] }}</td>
            <td>{{ $m['note'] }}</td>
            <td><span class="sw-tag">{{ $m['tag'] }}</span></td>
            <td class="sw-r sw-mono"><s>${{ $m['was'] }}</s></td>
            <td class="sw-r sw-table__now">${{ $m['from'] }}</td>
            <td class="sw-r">→</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="sw-sec__foot"><a href="/windows" class="sw-btn">All window types</a><a href="/brands" class="sw-btn">Compare by brand</a></div>
</section>

<section class="sw-sec" id="pricing">
  <header class="sw-sec__head"><span class="sw-mono">03 / Pricing</span><h2 class="sw-h2">Three tiers. One receipt.</h2></header>
  <div class="sw-tiers">
    @foreach($c['tiers'] as $j => $t)
      <div class="sw-tier"><span class="sw-mono">Tier {{ $j + 1 }}</span><h3>{{ $t['name'] }}</h3><p class="sw-tier__price"><b>${{ $t['from'] }}</b><s>${{ $t['was'] }}</s></p><p class="sw-mono">per window · installed</p><p class="sw-tier__brands">{{ $t['brands'] }}</p><a href="#quote" class="sw-btn sw-btn--full" data-hc-quote>Lock this price →</a></div>
    @endforeach
    <div class="sw-tier sw-tier--list">
      <span class="sw-mono">Included</span>
      <ul>@foreach($c['included'] as $item)<li><span>{{ $item }}</span><b class="sw-mono">✓</b></li>@endforeach<li class="sw-tier__total"><span>Hidden fees</span><b class="sw-mono">0</b></li></ul>
      <a href="/financing" class="sw-link">Financing from $0 down →</a>
    </div>
  </div>
</section>

<section class="sw-sec" id="doors">
  <header class="sw-sec__head"><span class="sw-mono">04 / Doors</span><h2 class="sw-h2">Doors, same crew, same day.</h2><p class="sw-lede">Entry, patio, multi-slide, pivot. Bundle with windows and the labor line drops.</p></header>
  <div class="sw-doors">
    @foreach($c['doors'] as $i => $dr)
      <a href="/doors/{{ $dr['slug'] }}" class="sw-door">
        @if($dr['image'] !== '')<x-img :src="$dr['image']" preset="card" loading="lazy" :alt="$dr['name'].' doors'" />@else<div class="sw-door__blank" aria-hidden="true"><span>{{ $dr['name'] }}</span></div>@endif
        <span class="sw-door__meta"><span class="sw-mono">{{ sprintf('%02d', $i + 1) }}</span><b>{{ $dr['name'] }}</b><span>{{ $dr['text'] }}</span></span>
      </a>
    @endforeach
  </div>
  <div class="sw-sec__foot"><a href="/doors" class="sw-btn">All doors</a></div>
</section>

<section class="sw-sec" id="process">
  <header class="sw-sec__head"><span class="sw-mono">05 / Process</span><h2 class="sw-h2">Four steps. Two are ours to worry about.</h2></header>
  <ol class="sw-steps">
    @foreach($c['steps'] as $i => [$t, $x])<li><b>0{{ $i + 1 }}</b><h3>{{ $t }}</h3><p>{{ $x }}</p></li>@endforeach
  </ol>
</section>

<section class="sw-sec" id="proof">
  <header class="sw-sec__head"><span class="sw-mono">06 / Before — After</span><h2 class="sw-h2">Same house. One day apart.</h2><p class="sw-lede">1971 single-pane aluminum → black aluminum-clad units + fiberglass entry. Drag the divider.</p></header>
  @include('home-concept._compare', ['p' => 'sw', 'labelBefore' => 'BEFORE / 1971', 'labelAfter' => 'AFTER / NOW'])
  <div class="sw-grid sw-stats">@foreach($c['stats'] as [$k, $v, $t])<div class="sw-cell"><span class="sw-mono">{{ $k }}</span><b>{{ $v }}</b><small>{{ $t }}</small></div>@endforeach</div>
  <p class="sw-mono sw-fine">{{ $c['statsNote'] }}</p>
</section>

<section class="sw-sec" id="reviews">
  <header class="sw-sec__head"><span class="sw-mono">07 / Reviews</span><h2 class="sw-h2">{{ $c['yelpCount'] }} Yelp reviews. {{ $c['yelpRating'] }} average.</h2><p class="sw-lede">Unedited. <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Source →</a></p></header>
  <ol class="sw-reviews">
    @forelse($c['reviews'] as $i => $r)
      <li><span class="sw-mono">{{ sprintf('%02d', $i + 1) }}</span><span class="sw-reviews__stars">{{ str_repeat('★', (int) $r['rating']) }}</span><p>{{ \Illuminate\Support\Str::limit(trim($r['text']), 240) }}</p><span class="sw-reviews__who">{{ $r['author'] }}@if(!empty($r['published_label'])) <small class="sw-mono">{{ $r['published_label'] }}</small>@endif</span></li>
    @empty
      <li><p>Read reviews on <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>.</p></li>
    @endforelse
  </ol>
</section>

<section class="sw-sec sw-sec--black" id="guarantee">
  <header class="sw-sec__head"><span class="sw-mono">08 / Guarantee</span><h2 class="sw-h2">Written. Transferable. Filed.</h2></header>
  <div class="sw-grid sw-guarantee">@foreach($c['guarantee'] as [$big, $t, $x])<div class="sw-cell"><b>{{ $big }}</b><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
  <p class="sw-mono sw-fine">{{ $c['guaranteeNote'] }}</p>
</section>

<section class="sw-sec" id="area">
  <header class="sw-sec__head"><span class="sw-mono">09 / Certifications &amp; Service area</span><h2 class="sw-h2">Nine counties. One showroom.</h2><p class="sw-lede">AAMA-certified, factory-trained. Showroom: {{ $c['showroom'] }} · {{ $c['hours'] }}.</p></header>
  <div class="sw-certs"><span class="sw-mono">Certified by</span>@foreach($c['certs'] as $cert)<img loading="lazy" src="{{ $cert['image'] }}" alt="{{ $cert['name'] }}" />@endforeach<b>AAMA</b></div>
  <ul class="sw-counties">@foreach($c['counties'] as $n => $s)<li><a href="/county-hub-pages/{{ $s }}"><span class="sw-mono">{{ sprintf('%02d', $loop->iteration) }}</span><b>{{ $n }}</b><small>County</small></a></li>@endforeach</ul>
</section>

<section class="sw-sec" id="pros">
  <header class="sw-sec__head"><span class="sw-mono">10 / For professionals</span><h2 class="sw-h2">Architects. Contractors. Property managers.</h2></header>
  <div class="sw-grid sw-pros">@foreach($c['pros'] as [$t, $x])<div class="sw-cell"><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
  <div class="sw-sec__foot"><a href="/contacts" class="sw-btn">Commercial desk →</a></div>
</section>

<section class="sw-sec" id="faq">
  <header class="sw-sec__head"><span class="sw-mono">11 / FAQ</span><h2 class="sw-h2">Five questions, answered.</h2></header>
  <div class="sw-faq">@foreach($c['faqs'] as $i => [$q, $a])<details @if($i === 0) open @endif><summary><span class="sw-mono">{{ sprintf('%02d', $i + 1) }}</span>{{ $q }}<i>+</i></summary><p>{{ $a }}</p></details>@endforeach</div>
  <div class="sw-sec__foot"><a href="/faq" class="sw-btn">Full FAQ</a></div>
</section>

<section class="sw-cta" id="quote">
  <div class="sw-cta__text">
    <span class="sw-mono">12 / Free estimate</span>
    <h2 class="sw-h2">Your dream home starts with a number.</h2>
    <p>Name + phone. A specialist calls within one business day, measures, and sends an itemized quote within 24 hours. {{ $c['discountLabel'] }} while it lasts.</p>
    <a href="tel:{{ $c['phoneTel'] }}" class="sw-cta__phone">{{ $c['phoneDisplay'] }}</a>
  </div>
  <div class="sw-cta__form">
    @include('home-concept._form', ['p' => 'sw', 'submitLabel' => 'Send request →'])
  </div>
</section>
</main>

<footer class="sw-footer">
  <div class="sw-footer__grid">
    <div><a href="/" class="sw-mark"><b>DW</b><span>Deluxe Windows<br>Burlingame, CA</span></a></div>
    <ul class="sw-footer__links">@foreach($c['footerLinks'] as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>
    <div class="sw-footer__contact sw-mono"><a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a><br>{{ $c['showroom'] }}<br>{{ $c['hours'] }}</div>
  </div>
  <p class="sw-footer__legal sw-mono">©{{ date('Y') }} Deluxe Windows, Inc. — {{ $c['license'] }} — 100% employee-owned — All rights reserved.</p>
</footer>

<div class="sw-sticky"><a href="tel:{{ $c['phoneTel'] }}">Call</a><a href="#quote" data-hc-quote>Get quote →</a></div>

@include('home-concept._switcher')
@include('home-concept._scripts')
</body>
</html>
