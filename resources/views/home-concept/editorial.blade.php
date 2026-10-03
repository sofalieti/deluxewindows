<!DOCTYPE html>
<html lang="en">
<head>
  @include('home-concept._head', [
    'fonts' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght,SOFT@9..144,300..700,0..100&family=Manrope:wght@400;500;600;700;800&display=swap',
    'themeColor' => '#15181c',
  ])
</head>
<body class="ed">

<header class="ed-header" data-hc-header>
  <div class="ed-header__bar">
    <a href="/" class="ed-wordmark" aria-label="Deluxe Windows — home"><span class="ed-wordmark__mark" aria-hidden="true"></span><span>Deluxe<br>Windows</span></a>
    <nav class="ed-nav" aria-label="Primary">
      <ul>
        @foreach($c['nav'] as $label => $menu)
          <li class="ed-nav__item">
            <a href="{{ $menu['href'] }}" class="ed-nav__link">{{ $label }}</a>
            <div class="ed-mega"><div class="ed-mega__inner">
              @foreach($menu['cols'] as $colTitle => $links)
                <div class="ed-mega__col"><p>{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></div>
              @endforeach
            </div></div>
          </li>
        @endforeach
        <li class="ed-nav__item"><a href="/special-offers" class="ed-nav__link ed-nav__link--offer">Special Offers</a></li>
      </ul>
    </nav>
    <div class="ed-header__actions">
      <a href="tel:{{ $c['phoneTel'] }}" class="ed-header__phone">{{ $c['phoneDisplay'] }}</a>
      <a href="#quote" class="ed-btn ed-btn--ink ed-btn--sm" data-hc-quote>Get a quote</a>
      <button type="button" class="ed-burger" data-hc-burger aria-expanded="false" aria-controls="ed-drawer" aria-label="Menu"><span></span><span></span></button>
    </div>
  </div>
  <div class="ed-drawer" id="ed-drawer" data-hc-drawer hidden>
    @foreach($c['nav'] as $label => $menu)
      <details class="ed-drawer__group"><summary>{{ $label }}</summary>
        @foreach($menu['cols'] as $colTitle => $links)<p class="ed-drawer__sub">{{ $colTitle }}</p><ul>@foreach($links as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul>@endforeach
      </details>
    @endforeach
    <a href="/special-offers" class="ed-drawer__offer">Special Offers · {{ $c['discountLabel'] }}</a>
    <a href="tel:{{ $c['phoneTel'] }}" class="ed-btn ed-btn--ink ed-btn--block">Call {{ $c['phoneDisplay'] }}</a>
  </div>
</header>

<main>
<section class="ed-hero">
  <div class="ed-hero__text">
    <p class="ed-kicker"><span>Burlingame, California</span><i></i><span>{{ $c['discountLabel'] }} through {{ $c['promoEnd'] ? $c['promoEnd']->format('F j') : 'this season' }}</span></p>
    <h1 class="ed-display">Windows that pay<br><em>for themselves,</em><br>installed by the people<br><em>who own the company.</em></h1>
    <p class="ed-hero__lede">Deluxe Windows is a 100% employee-owned window &amp; door company. We carry every major brand, quote in writing within 24 hours, and install most homes in a day or two — from <strong>$499 per window</strong>, everything included.</p>
    <div class="ed-hero__cta">
      <a href="#quote" class="ed-btn ed-btn--accent" data-hc-quote>Start my free quote <span aria-hidden="true">→</span></a>
      <a href="#windows" class="ed-textlink">Browse materials &amp; prices</a>
    </div>
    <dl class="ed-facts">
      <div><dt>Yelp</dt><dd>{{ $c['yelpRating'] }}<small>/ {{ $c['yelpCount'] }} reviews</small></dd></div>
      <div><dt>Experience</dt><dd>30<small>+ years</small></dd></div>
      <div><dt>Install</dt><dd>1–2<small>days</small></dd></div>
      <div><dt>License</dt><dd><small>CA #</small>695262</dd></div>
    </dl>
  </div>
  <figure class="ed-hero__media">
    <x-img :src="$c['images']['hero']" preset="hero_bg" loading="eager" alt="Bay Area home after a Deluxe Windows installation" />
    <figcaption>Craftsman, San Mateo County — Marvin Elevate, black exterior</figcaption>
  </figure>
  <aside class="ed-ticket" id="quote" aria-labelledby="ed-ticket-title">
    <div class="ed-ticket__head"><span>Nº 01 — Free estimate</span><b>{{ $c['discountPercent'] }}<small>% off</small></b></div>
    <h2 id="ed-ticket-title" class="ed-ticket__title">Your price, in writing, within 24 hours.</h2>
    @include('home-concept._form', ['p' => 'ed'])
    <div class="ed-ticket__tear" aria-hidden="true"></div>
  </aside>
</section>

<section class="ed-ticker" aria-label="Trust indicators">
  <div class="ed-ticker__track">
    @for($i = 0; $i < 2; $i++)
      <span class="ed-ticker__group" @if($i) aria-hidden="true" @endif>
        <span>AAMA-certified installers</span><i></i><span>100% employee-owned</span><i></i><span>{{ $c['yelpRating'] }}★ · {{ $c['yelpCount'] }} Yelp reviews</span><i></i><span>Financing from $0 down</span><i></i><span>{{ $c['license'] }}</span><i></i><span>Showroom in Burlingame</span><i></i><span>{{ $c['discountLabel'] }} — limited time</span><i></i>
      </span>
    @endfor
  </div>
</section>

<div class="ed-layout">
  <nav class="ed-index" aria-label="On this page" data-hc-index>
    <ol>
      @foreach([['brands','Brands'],['windows','Windows'],['pricing','Pricing'],['doors','Doors'],['process','Process'],['proof','Before / After'],['reviews','Reviews'],['guarantee','Guarantee'],['area','Service Area'],['pros','For Pros'],['faq','FAQ']] as $i => [$id, $label])
        <li><a href="#{{ $id }}"><span>{{ sprintf('%02d', $i + 1) }}</span>{{ $label }}</a></li>
      @endforeach
    </ol>
  </nav>

  <div class="ed-flow">

  <section class="ed-sec" id="brands">
    <header class="ed-sec__head">
      <span class="ed-num">01</span>
      <h2 class="ed-h2">Eleven brands.<br><em>Zero house brand.</em></h2>
      <p class="ed-lede">Big-box stores sell you whatever they private-label. We are an independent dealer for Marvin, Andersen, Milgard, Anlin and seven more — so the recommendation is about your house, not our margin. Sometimes the cheaper window is the right one. We will say so.</p>
    </header>
    <ul class="ed-brandlist">
      @foreach($c['brands'] as $i => $b)
        <li><a href="/brands/{{ $b['slug'] }}"><span class="ed-brandlist__i">{{ sprintf('%02d', $i + 1) }}</span><span class="ed-brandlist__name">{{ $b['name'] }}</span><span class="ed-brandlist__logo"><x-img :src="$b['image']" preset="brand_grid" loading="lazy" :alt="$b['name']" /></span><span class="ed-brandlist__go" aria-hidden="true">→</span></a></li>
      @endforeach
    </ul>
  </section>

  <section class="ed-sec" id="windows">
    <header class="ed-sec__head ed-sec__head--row">
      <div><span class="ed-num">02</span><h2 class="ed-h2">The material index.<br><em>Seven ways to frame a view.</em></h2></div>
      <p class="ed-lede">Per window, installed, with {{ $c['discountLabel'] }} applied. Hover a row to see it; click to compare brands, glass and hardware.</p>
    </header>
    <div class="ed-matrix" data-hc-matrix>
      <div class="ed-matrix__rows">
        <div class="ed-matrix__hdr" aria-hidden="true"><span>Material</span><span>Best for</span><span>Why it wins</span><span>From</span></div>
        @foreach($c['materials'] as $i => $m)
          <a href="/windows/{{ $m['slug'] }}" class="ed-matrix__row" data-hc-row data-image="{{ $m['image'] }}" data-name="{{ $m['name'] }}">
            <span class="ed-matrix__name"><span class="ed-matrix__i">{{ sprintf('%02d', $i + 1) }}</span>{{ $m['name'] }}<span class="ed-tag">{{ $m['tag'] }}</span></span>
            <span class="ed-matrix__for">{{ $m['for'] }}</span>
            <span class="ed-matrix__note">{{ $m['note'] }}</span>
            <span class="ed-matrix__price"><s>${{ $m['was'] }}</s><b>${{ $m['from'] }}</b></span>
          </a>
        @endforeach
      </div>
      <div class="ed-matrix__preview" aria-hidden="true">
        <div class="ed-matrix__frame"><img src="{{ $c['images']['samples'] }}" alt="" data-hc-preview-img /></div>
        <p data-hc-preview-cap>Corner samples: aluminum, vinyl, wood-clad, fiberglass</p>
      </div>
    </div>
    <div class="ed-sec__foot"><a href="/windows" class="ed-textlink">All window types</a><a href="/brands" class="ed-textlink">Compare by brand</a></div>
  </section>

  <section class="ed-sec" id="pricing">
    <header class="ed-sec__head"><span class="ed-num">03</span><h2 class="ed-h2">The receipt, before you buy.<br><em>Nothing hides in the fine print.</em></h2></header>
    <div class="ed-receipt">
      <div class="ed-receipt__tiers">
        @foreach($c['tiers'] as $j => $t)
          <div class="ed-receipt__tier"><span class="ed-receipt__i">Tier {{ $j + 1 }}</span><h3>{{ $t['name'] }}</h3><p class="ed-receipt__price"><s>${{ $t['was'] }}</s><b>${{ $t['from'] }}</b><small>per window, installed</small></p><p class="ed-receipt__brands">{{ $t['brands'] }}</p></div>
        @endforeach
      </div>
      <div class="ed-receipt__lines">
        <p class="ed-receipt__lines-title">Included in every line</p>
        <ul>
          @foreach($c['included'] as $item)<li><span>{{ $item }}</span><b>incl.</b></li>@endforeach
          <li class="ed-receipt__total"><span>Surprise line items</span><b>$0</b></li>
        </ul>
        <a href="/financing" class="ed-textlink">Monthly plans from $0 down</a>
      </div>
    </div>
  </section>

  <section class="ed-sec" id="doors">
    <header class="ed-sec__head ed-sec__head--row">
      <div><span class="ed-num">04</span><h2 class="ed-h2">Doors, same crew.<br><em>One visit, one warranty file.</em></h2></div>
      <p class="ed-lede">Entry, patio, multi-slide and pivot. Bundle doors with windows and the labor line drops.</p>
    </header>
    <div class="ed-rail" data-hc-rail="doors">
      @foreach($c['doors'] as $i => $dr)
        <a href="/doors/{{ $dr['slug'] }}" class="ed-rail__card {{ $dr['image'] === '' ? 'ed-rail__card--text' : '' }}">
          @if($dr['image'] !== '')<x-img :src="$dr['image']" preset="card" loading="lazy" :alt="$dr['name'].' doors'" />@endif
          <span class="ed-rail__meta"><span class="ed-rail__i">{{ sprintf('%02d', $i + 1) }}</span><span class="ed-rail__name">{{ $dr['name'] }}</span><span class="ed-rail__text">{{ $dr['text'] }}</span></span>
        </a>
      @endforeach
    </div>
    <div class="ed-rail__controls"><button type="button" class="ed-rail__btn" data-hc-rail-prev="doors" aria-label="Previous">←</button><button type="button" class="ed-rail__btn" data-hc-rail-next="doors" aria-label="Next">→</button><a href="/doors" class="ed-textlink">All doors</a></div>
  </section>

  <section class="ed-sec" id="process">
    <header class="ed-sec__head"><span class="ed-num">05</span><h2 class="ed-h2">Four steps.<br><em>Two of them are ours to worry about.</em></h2></header>
    <div class="ed-process">
      <ol class="ed-timeline">
        @foreach($c['steps'] as $i => [$sTitle, $sText])
          <li><span class="ed-timeline__i">{{ sprintf('%02d', $i + 1) }}</span><h3>{{ $sTitle }}</h3><p>{{ $sText }}</p></li>
        @endforeach
      </ol>
      <figure class="ed-process__media"><x-img :src="$c['images']['installer']" preset="cta" loading="lazy" alt="Installer setting a new window" /><figcaption><b>1–2 days</b> for a typical whole-home replacement</figcaption></figure>
    </div>
  </section>

  <section class="ed-sec" id="proof">
    <header class="ed-sec__head ed-sec__head--row">
      <div><span class="ed-num">06</span><h2 class="ed-h2">Same house.<br><em>One install day apart.</em></h2></div>
      <p class="ed-lede">Drag the line. Black aluminum-clad units and a fiberglass entry door replaced single-pane aluminum from 1971.</p>
    </header>
    @include('home-concept._compare', ['p' => 'ed', 'labelBefore' => '1971', 'labelAfter' => 'Now'])
    <dl class="ed-stats">
      @foreach($c['stats'] as [$k, $v, $t])<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd><p>{{ $t }}</p></div>@endforeach
    </dl>
    <p class="ed-fine">{{ $c['statsNote'] }}</p>
  </section>

  <section class="ed-sec" id="reviews">
    <header class="ed-sec__head ed-sec__head--row">
      <div><span class="ed-num">07</span><h2 class="ed-h2">{{ $c['yelpCount'] }} neighbors.<br><em>Unedited, from Yelp.</em></h2></div>
      <p class="ed-lede"><b>{{ $c['yelpRating'] }} / 5</b> overall · <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">read all on Yelp</a></p>
    </header>
    <div class="ed-quotes">
      @forelse($c['reviews'] as $r)
        <blockquote class="ed-quote"><p>“{{ \Illuminate\Support\Str::limit(trim($r['text']), 260) }}”</p><footer><span>{{ $r['author'] }}</span><span class="ed-quote__stars" aria-label="{{ $r['rating'] }} of 5">{{ str_repeat('★', (int) $r['rating']) }}</span>@if(!empty($r['published_label']))<span class="ed-quote__when">{{ $r['published_label'] }}</span>@endif</footer></blockquote>
      @empty
        <p class="ed-lede">Read our reviews on <a href="{{ $c['yelpUrl'] }}" target="_blank" rel="noopener noreferrer">Yelp</a>.</p>
      @endforelse
    </div>
  </section>

  <section class="ed-sec ed-sec--dark" id="guarantee">
    <header class="ed-sec__head"><span class="ed-num">08</span><h2 class="ed-h2">Written down.<br><em>Not promised on a handshake.</em></h2></header>
    <div class="ed-guarantee">
      @foreach($c['guarantee'] as [$big, $title, $text])<div><b>{{ $big }}</b><h3>{{ $title }}</h3><p>{{ $text }}</p></div>@endforeach
    </div>
    <p class="ed-fine">{{ $c['guaranteeNote'] }}</p>
  </section>

  <section class="ed-sec" id="area">
    <header class="ed-sec__head ed-sec__head--row">
      <div><span class="ed-num">09</span><h2 class="ed-h2">Certified crews.<br><em>Nine counties, one showroom.</em></h2></div>
      <p class="ed-lede">Factory-trained and AAMA-certified — because the warranty is only as good as the install. Showroom: {{ $c['showroom'] }} · {{ $c['hours'] }}.</p>
    </header>
    <div class="ed-certs"><span>Certified by</span>@foreach($c['certs'] as $cert)<img loading="lazy" src="{{ $cert['image'] }}" alt="{{ $cert['name'] }}" />@endforeach<b>AAMA</b></div>
    <ul class="ed-counties">@foreach($c['counties'] as $n => $s)<li><a href="/county-hub-pages/{{ $s }}"><span>{{ $n }}</span><small>County</small></a></li>@endforeach</ul>
    <div class="ed-sec__foot"><a href="/gallery" class="ed-textlink">Recent installs near you</a></div>
  </section>

  <section class="ed-sec" id="pros">
    <header class="ed-sec__head"><span class="ed-num">10</span><h2 class="ed-h2">For the trade.<br><em>One accountable partner.</em></h2></header>
    <div class="ed-pros">@foreach($c['pros'] as [$t, $x])<div><h3>{{ $t }}</h3><p>{{ $x }}</p></div>@endforeach</div>
    <div class="ed-sec__foot"><a href="/contacts" class="ed-textlink">Talk to the commercial desk</a></div>
  </section>

  <section class="ed-sec" id="faq">
    <header class="ed-sec__head"><span class="ed-num">11</span><h2 class="ed-h2">Five questions<br><em>everyone asks first.</em></h2></header>
    <div class="ed-faq">
      @foreach($c['faqs'] as $i => [$q, $a])<details @if($i === 0) open @endif><summary><span>{{ sprintf('%02d', $i + 1) }}</span>{{ $q }}</summary><p>{{ $a }}</p></details>@endforeach
    </div>
    <div class="ed-sec__foot"><a href="/faq" class="ed-textlink">Full FAQ</a></div>
  </section>

  </div>
</div>

<section class="ed-final">
  <figure class="ed-final__media"><x-img :src="$c['images']['consult']" preset="cta" loading="lazy" alt="Consultation at the Deluxe Windows showroom" /></figure>
  <div class="ed-final__text">
    <span class="ed-num">Nº 02</span>
    <h2 class="ed-h2">Your dream home starts<br><em>with one conversation.</em></h2>
    <p>Tell us about the project. We measure, quote in writing within 24 hours, and take care of everything after that — {{ $c['discountLabel'] }} while the promotion lasts.</p>
    <div class="ed-hero__cta"><a href="#quote" class="ed-btn ed-btn--bone" data-hc-quote>Start my free quote <span aria-hidden="true">→</span></a><a href="tel:{{ $c['phoneTel'] }}" class="ed-textlink ed-textlink--light">Or call {{ $c['phoneDisplay'] }}</a></div>
  </div>
</section>
</main>

<footer class="ed-footer">
  <div class="ed-footer__top">
    <a href="/" class="ed-wordmark ed-wordmark--lg" aria-label="Deluxe Windows — home"><span class="ed-wordmark__mark" aria-hidden="true"></span><span>Deluxe<br>Windows</span></a>
    <nav aria-label="Footer"><ul class="ed-footer__links">@foreach($c['footerLinks'] as $t => $h)<li><a href="{{ $h }}">{{ $t }}</a></li>@endforeach</ul></nav>
    <address class="ed-footer__contact"><a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a><span>{{ $c['showroom'] }}</span><span>{{ $c['hours'] }}</span></address>
  </div>
  <div class="ed-footer__bottom"><span>©{{ date('Y') }} Deluxe Windows, Inc. · {{ $c['license'] }} · All rights reserved.</span><span>100% employee-owned · Burlingame, California</span></div>
</footer>

<div class="ed-sticky"><a href="tel:{{ $c['phoneTel'] }}">Call</a><a href="#quote" data-hc-quote>Free quote · {{ $c['discountLabel'] }}</a></div>

@include('home-concept._switcher')
@include('home-concept._scripts')
</body>
</html>
