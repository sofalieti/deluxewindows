@extends('layouts.classic')

@section('wfPage', '6841df5688ca2f74fd53ec90')
@section('bodyClass', 'home-concept-page')

@section('head')
  @php
    $hcCssPath = public_path('webflow-overrides/home-concept.css');
    $hcCssVersion = is_file($hcCssPath) ? (string) filemtime($hcCssPath) : '1';
  @endphp
  <link href="/webflow-overrides/home-concept.css?v={{ $hcCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@php
  $promo = app(\App\Services\PromotionControlService::class);
  $discountLabel = $promo->globalDiscountLabel();
  $promoEnd = $promo->endDate();
  $phoneDisplay = site_phone_display();
  $phoneTel = site_phone_tel();
  $yelp = app(\App\Services\YelpReviewsService::class)->payload();
  $yelpRating = $yelp['business']['rating_label'] ?? '4.5';
  $yelpCount = $yelp['business']['reviews_label'] ?? '257';
  $yelpUrl = $yelp['business']['yelp_url'] ?? 'https://www.yelp.com/biz/deluxe-windows-burlingame-3';

  // Canonical "from" pricing by material (installed, promo).
  $tierBySlug = function (string $slug): array {
      $s = strtolower($slug);
      if (str_contains($s, 'vinyl')) {
          return ['from' => 499, 'was' => 832, 'tier' => 'Best value', 'best' => 'Budget-smart upgrades & rentals'];
      }
      if (str_contains($s, 'steel') || (str_contains($s, 'wood') && ! str_contains($s, 'clad'))) {
          return ['from' => 589, 'was' => 982, 'tier' => 'Premium', 'best' => 'Historic & design-forward homes'];
      }
      return ['from' => 549, 'was' => 915, 'tier' => 'Most popular', 'best' => 'Coastal climate & large openings'];
  };

  $steps = [
      ['n' => '01', 't' => 'Free in-home consultation', 'd' => 'A product specialist (not a salesperson on commission) measures, listens and shows real samples. 45–60 minutes, zero pressure.'],
      ['n' => '02', 't' => 'Written quote in 24 hours', 'd' => 'Line-item pricing with brand, glass package and warranty spelled out. Price locked for 30 days.'],
      ['n' => '03', 't' => 'Factory order & scheduling', 'd' => 'Windows are built to the 1/8" for your openings. Typical lead time 3–6 weeks; we handle permits where required.'],
      ['n' => '04', 't' => 'Install day & clean-up', 'd' => 'Most homes finish in 1–2 days. Old units hauled away, openings sealed and trimmed, every window operated with you before we leave.'],
  ];

  $included = [
      'Removal & disposal of old windows',
      'Low-E, argon-filled dual-pane glass',
      'Professional installation by AAMA-certified crews',
      'Interior & exterior trim, caulking and sealing',
      'Haul-away and full job-site clean-up',
      'Manufacturer + installation warranty paperwork',
  ];

  $counties = [
      ['San Mateo', 'san-mateo-county'], ['San Francisco', 'san-francisco-county'], ['Santa Clara', 'santa-clara-county'],
      ['Alameda', 'alameda-county'], ['Contra Costa', 'contra-costa-county'], ['Marin', 'marin-county'],
      ['Sonoma', 'sonoma-county'], ['Napa', 'napa-county'], ['Solano', 'solano-county'],
  ];

  $faqs = [
      ['q' => 'How much do replacement windows really cost?', 'a' => 'With the current promotion, vinyl starts at $499 per window installed, wood-clad / fiberglass / aluminum at $549 and wood or steel at $589. Your exact price depends on size, glass package and how many openings you do at once — the quote is free and itemized.'],
      ['q' => 'Do I have to replace every window at once?', 'a' => 'No. Many homeowners phase the work room by room. That said, doing more openings in one visit lowers the per-window price, so we will show you both options.'],
      ['q' => 'How long does installation take?', 'a' => 'A typical 8–12 window home is finished in one to two days. We work room by room, so your home is never left open overnight.'],
      ['q' => 'Is financing available?', 'a' => 'Yes — we offer flexible monthly plans including promotional 0% options for qualified buyers. Ask your consultant for the current terms.'],
      ['q' => 'What warranty do I get?', 'a' => 'Vinyl windows carry a full lifetime transferable warranty on parts and labor; other materials include a 20-year glass warranty and 10 years on all other parts, plus the manufacturer’s coverage.'],
  ];
@endphp

@section('content')

{{-- ============ HERO ============ --}}
<section class="hc-hero" aria-labelledby="hc-hero-title">
  <div class="hc-hero__bg" aria-hidden="true">
    <x-img src="/webflow-assets/images/home-concept/home-concept-hero.jpg" preset="hero_bg" loading="eager" alt="" class="hc-hero__bg-img" />
  </div>
  <div class="hc-container hc-hero__grid">
    <div class="hc-hero__copy">
      <span class="hc-eyebrow hc-eyebrow--light">
        <span class="hc-eyebrow__dot"></span>
        {{ $discountLabel }} on windows &amp; doors{{ $promoEnd ? ' · ends '.$promoEnd->format('M j') : '' }}
      </span>
      <h1 id="hc-hero-title" class="hc-hero__title">New Windows. Lower Bills.<br /><em>One trusted Bay Area team.</em></h1>
      <p class="hc-hero__lead">
        Premium window &amp; door replacement from <strong>$499 installed</strong>. 30+ years, 100% employee-owned,
        and every major brand under one roof — so you get the right window, not the one we’re paid to push.
      </p>
      <div class="hc-hero__actions">
        <a href="#hc-quote" class="hc-btn hc-btn--primary">Get my free quote</a>
        <a href="tel:{{ $phoneTel }}" class="hc-btn hc-btn--ghost">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.3.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1L6.6 10.8Z"/></svg>
          {{ $phoneDisplay }}
        </a>
      </div>
      <ul class="hc-hero__proof" aria-label="Why homeowners choose us">
        <li><strong>{{ $yelpRating }}★</strong> on Yelp · {{ $yelpCount }} reviews</li>
        <li><strong>Lifetime</strong> warranty on vinyl</li>
        <li><strong>1–2 days</strong> typical install</li>
      </ul>
    </div>

    <div class="hc-quote-card" id="hc-quote">
      <div class="hc-quote-card__head">
        <div>
          <p class="hc-quote-card__kicker">Free estimate · no pressure</p>
          <h2 class="hc-quote-card__title">Get your exact price in 24h</h2>
        </div>
        <span class="hc-badge">{{ $discountLabel }}</span>
      </div>
      <div class="w-form hc-form-wrap">
        <form id="hc-quote-form" name="hc-quote-form" method="get" class="hc-form" data-form-id="Home Concept Form" aria-label="Request a free estimate">
          <input type="hidden" name="Form ID" value="Home Concept Form" />
          <label class="hc-field">
            <span class="hc-field__label">Full name</span>
            <input class="hc-input" type="text" name="Name" autocomplete="name" placeholder="Jane Doe" required maxlength="256" />
          </label>
          <label class="hc-field">
            <span class="hc-field__label">Phone</span>
            <input class="hc-input" type="tel" name="Phone" autocomplete="tel" inputmode="tel" placeholder="{{ $phoneDisplay }}" required maxlength="256" />
          </label>
          <label class="hc-field">
            <span class="hc-field__label">Email <span class="hc-field__opt">(optional)</span></span>
            <input class="hc-input" type="email" name="Email" autocomplete="email" placeholder="jane@email.com" maxlength="256" />
          </label>
          <label class="hc-field">
            <span class="hc-field__label">City</span>
            <input class="hc-input" type="text" name="Subject" autocomplete="address-level2" placeholder="San Mateo" maxlength="256" />
          </label>
          <label class="hc-field hc-field--full">
            <span class="hc-field__label">What are you replacing? <span class="hc-field__opt">(optional)</span></span>
            <input class="hc-input" type="text" name="Message" placeholder="e.g. 8 windows + patio door" maxlength="5000" />
          </label>
          <button type="submit" class="hc-btn hc-btn--primary hc-btn--block" data-wait="Please wait...">Request a free estimate</button>
          <p class="hc-form__note">We call or text within one business day. No spam, no sharing your info.</p>
        </form>
        <div class="w-form-done hc-form-done" tabindex="-1" role="region" aria-label="Form success">
          <strong>Thank you!</strong> Your request is in — a specialist will reach out within one business day.
        </div>
        <div class="w-form-fail hc-form-fail" tabindex="-1" role="region" aria-label="Form failure">
          Something went wrong. Please call <a href="tel:{{ $phoneTel }}">{{ $phoneDisplay }}</a>.
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ TRUST STRIP ============ --}}
<section class="hc-trust" aria-label="Trust indicators">
  <div class="hc-container hc-trust__grid">
    <div class="hc-trust__item"><strong>30+</strong><span>years in the Bay Area</span></div>
    <div class="hc-trust__item"><strong>{{ $yelpRating }}★</strong><span><a href="{{ $yelpUrl }}" target="_blank" rel="noopener noreferrer">{{ $yelpCount }} Yelp reviews</a></span></div>
    <div class="hc-trust__item"><strong>100%</strong><span>employee-owned</span></div>
    <div class="hc-trust__item"><strong>AAMA</strong><span>certified installers</span></div>
    <div class="hc-trust__item"><strong>11</strong><span>brands, one showroom</span></div>
    <div class="hc-trust__item"><strong>CA Lic.</strong><span>#695262 · insured</span></div>
  </div>
</section>

{{-- ============ BRANDS ============ --}}
<section class="hc-section hc-brands" aria-labelledby="hc-brands-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--split">
      <div>
        <span class="hc-eyebrow">Independent dealer</span>
        <h2 id="hc-brands-title" class="hc-h2">Every major brand. Honest advice on which one fits.</h2>
      </div>
      <p class="hc-section__intro">
        Big-box stores push one house brand. We stock Marvin, Andersen, Milgard, Anlin and more — and we tell you
        when the cheaper one is the smarter buy.
      </p>
    </div>
    <div class="hc-brand-wall">
      @foreach([
        ['/brands/marvin', '/webflow-assets/images/6915aaca08003de3e1e57018_marvin-logo-black.svg', 'Marvin'],
        ['/brands/andersen', '/webflow-assets/images/6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg', 'Andersen'],
        ['/brands/milgard', '/webflow-assets/images/6915aaea85f921adbca8a4e7_milgard.svg', 'Milgard'],
        ['/brands/anlin', '/webflow-assets/images/6915c80af96503367881f15f_anlin2.svg', 'Anlin'],
        ['/brands/jeld-wen', '/webflow-assets/images/6915aa60264a3c99f69524c6_jv.svg', 'Jeld-Wen'],
        ['/brands/simonton', '/webflow-assets/images/6915aa3a24afaaa0a93dd455_Simonton_PrimaryLogo_Inline_RGB_Gradient_0822-1-2048x427.avif', 'Simonton'],
        ['/brands/ply-gem', '/webflow-assets/images/6915aa80238022f9197f6973_pl.svg', 'Ply Gem'],
        ['/brands/alside', '/webflow-assets/images/6915b29da8bcdcb16ec593b6_alside-logo.svg', 'Alside'],
        ['/brands/western-window-systems', '/webflow-assets/images/6915b390bad100b6e6176ea7_westerngroup.svg', 'Western Window Systems'],
        ['/brands/all-weather-architectural-aluminum', '/webflow-assets/images/6915bedcc5e0152198130ace_footer-logo__1__2-removebg-preview.avif', 'All Weather'],
        ['/brands/italwindows', '/webflow-assets/images/6915bd3fcaf3c1f1ff04d9dd_italwindows.svg', 'Italwindows'],
      ] as [$bHref, $bImg, $bAlt])
        <a href="{{ $bHref }}" class="hc-brand-wall__item" aria-label="{{ $bAlt }} windows">
          <x-img :src="$bImg" preset="brand_grid" loading="lazy" :alt="$bAlt" />
        </a>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ WINDOWS BY MATERIAL ============ --}}
<section class="hc-section hc-windows" aria-labelledby="hc-windows-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--split">
      <div>
        <span class="hc-eyebrow">Windows</span>
        <h2 id="hc-windows-title" class="hc-h2">Pick the material. We’ll match the brand.</h2>
      </div>
      <p class="hc-section__intro">Prices are per window, installed, with the current {{ $discountLabel }} applied. No hidden “disposal” or “trim” fees.</p>
    </div>

    <div class="hc-material-grid">
      @forelse($homeWindows as $hw)
        @php $tier = $tierBySlug($hw['slug']); @endphp
        <a href="/windows/{{ $hw['slug'] }}" class="hc-material-card">
          <div class="hc-material-card__media">
            @if($hw['image'])
              <x-img :src="$hw['image']" preset="card" loading="lazy" :alt="$hw['name']" />
            @endif
            <span class="hc-chip">{{ $tier['tier'] }}</span>
          </div>
          <div class="hc-material-card__body">
            <h3 class="hc-h3">{{ $hw['name'] }}</h3>
            <p class="hc-material-card__best">Best for: {{ $tier['best'] }}</p>
            @if($hw['summary'])
              <p class="hc-material-card__summary">{{ \Illuminate\Support\Str::limit($hw['summary'], 110) }}</p>
            @endif
            <div class="hc-price">
              <span class="hc-price__was">${{ $tier['was'] }}</span>
              <span class="hc-price__now">from ${{ $tier['from'] }}</span>
              <span class="hc-price__unit">/ window installed</span>
            </div>
            <span class="hc-link">Compare brands &amp; glass packages →</span>
          </div>
        </a>
      @empty
        <p>Window materials are loading…</p>
      @endforelse
    </div>
    <div class="hc-section__foot">
      <a href="/windows" class="hc-btn hc-btn--outline">See all window types</a>
    </div>
  </div>
</section>

{{-- ============ TRANSPARENT PRICING ============ --}}
<section class="hc-section hc-pricing" aria-labelledby="hc-pricing-title">
  <div class="hc-container hc-pricing__grid">
    <div class="hc-pricing__intro">
      <span class="hc-eyebrow">Transparent pricing</span>
      <h2 id="hc-pricing-title" class="hc-h2">What a window actually costs — and what’s inside the number.</h2>
      <p class="hc-section__intro">
        Most quotes hide labor, disposal and trim in the fine print. Ours doesn’t. Every price below is installed and includes:
      </p>
      <ul class="hc-check-list">
        @foreach($included as $item)
          <li>{{ $item }}</li>
        @endforeach
      </ul>
      <a href="/financing" class="hc-link">Financing from $0 down · see monthly plans →</a>
    </div>
    <div class="hc-tiers">
      @foreach([
        ['Vinyl', 499, 832, 'Anlin · Milgard · Simonton', false],
        ['Wood Clad · Fiberglass · Aluminum', 549, 915, 'Andersen · Marvin · Milgard Ultra · WWS', true],
        ['Wood · Steel', 589, 982, 'Marvin · Jeld-Wen · Italwindows', false],
      ] as [$tName, $tFrom, $tWas, $tBrands, $tHot])
        <div class="hc-tier {{ $tHot ? 'hc-tier--hot' : '' }}">
          @if($tHot)<span class="hc-tier__flag">Most chosen</span>@endif
          <h3 class="hc-tier__name">{{ $tName }}</h3>
          <div class="hc-tier__price"><s>${{ $tWas }}</s> <strong>${{ $tFrom }}</strong></div>
          <p class="hc-tier__unit">per window, installed</p>
          <p class="hc-tier__brands">{{ $tBrands }}</p>
          <a href="#hc-quote" class="hc-btn {{ $tHot ? 'hc-btn--primary' : 'hc-btn--outline' }} hc-btn--sm">Get this price</a>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ DOORS ============ --}}
<section class="hc-section hc-doors" aria-labelledby="hc-doors-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--split">
      <div>
        <span class="hc-eyebrow">Doors</span>
        <h2 id="hc-doors-title" class="hc-h2">Entry, patio and multi-slide doors that match your new windows.</h2>
      </div>
      <p class="hc-section__intro">Same crews, same warranty paperwork, one install visit — bundle doors with windows and save on labor.</p>
    </div>
    <div class="hc-door-grid">
      @foreach([
        ['/doors/vinyl-doors', '/webflow-assets/images/6862d4e603255742b1319d0f_Frame%2048.avif', 'Vinyl Doors', 'Sliding & French patio doors with the lowest upkeep.'],
        ['/doors/wood-clad-doors', '/webflow-assets/images/687e2bbbcf84c63258838bc4_homeguide-marvin-signature-windows-and-doors.avif', 'Wood Clad Doors', 'Warm interiors, weather-proof exteriors, big openings.'],
        ['/doors/fiberglass-doors', '/webflow-assets/images/684e94a86602a96b9775c003_Frame%2048.avif', 'Fiberglass Doors', 'Entry doors that look like wood and never warp.'],
      ] as [$dHref, $dImg, $dName, $dText])
        <a href="{{ $dHref }}" class="hc-door-card">
          <div class="hc-door-card__media"><x-img :src="$dImg" preset="card" loading="lazy" :alt="$dName" /></div>
          <div class="hc-door-card__body">
            <h3 class="hc-h3">{{ $dName }}</h3>
            <p>{{ $dText }}</p>
            <span class="hc-link">Explore →</span>
          </div>
        </a>
      @endforeach
    </div>
    <div class="hc-section__foot">
      <a href="/doors" class="hc-btn hc-btn--outline">See all doors</a>
    </div>
  </div>
</section>

{{-- ============ HOW IT WORKS ============ --}}
<section class="hc-section hc-process" aria-labelledby="hc-process-title">
  <div class="hc-container hc-process__grid">
    <div class="hc-process__media">
      <x-img src="/webflow-assets/images/home-concept/home-concept-installer.jpg" preset="cta" loading="lazy" alt="Deluxe Windows installer setting a new window" />
      <div class="hc-process__float">
        <strong>1–2 days</strong>
        <span>typical whole-home install</span>
      </div>
    </div>
    <div>
      <span class="hc-eyebrow">How it works</span>
      <h2 id="hc-process-title" class="hc-h2">From first call to last window — in four clear steps.</h2>
      <ol class="hc-steps">
        @foreach($steps as $step)
          <li class="hc-step">
            <span class="hc-step__num">{{ $step['n'] }}</span>
            <div>
              <h3 class="hc-step__title">{{ $step['t'] }}</h3>
              <p>{{ $step['d'] }}</p>
            </div>
          </li>
        @endforeach
      </ol>
      <a href="#hc-quote" class="hc-btn hc-btn--primary">Book my free consultation</a>
    </div>
  </div>
</section>

{{-- ============ BEFORE / AFTER ============ --}}
<section class="hc-section hc-compare" aria-labelledby="hc-compare-title">
  <div class="hc-container">
    <div class="hc-section__head">
      <span class="hc-eyebrow">Before &amp; after</span>
      <h2 id="hc-compare-title" class="hc-h2">Drag to see what one install day changes.</h2>
    </div>
    <div class="dw-compare" data-dw-compare>
      <div class="dw-compare__viewport">
        <x-img src="/webflow-assets/images/home-concept/home-concept-after.jpg" preset="hero_bg" loading="lazy" alt="After: new black-frame windows and fiberglass entry door" class="dw-compare__image dw-compare__image--after" />
        <div class="dw-compare__before" data-dw-compare-before>
          <x-img src="/webflow-assets/images/home-concept/home-concept-before.jpg" preset="hero_bg" loading="lazy" alt="Before: old single-pane aluminum windows" class="dw-compare__image dw-compare__image--before" />
        </div>
        <div class="dw-compare__divider" data-dw-compare-divider aria-hidden="true">
          <span class="dw-compare__handle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M10 6 4 12l6 6V6Zm4 0v12l6-6-6-6Z"/></svg>
          </span>
        </div>
        <span class="dw-compare__badge dw-compare__badge--before">Before</span>
        <span class="dw-compare__badge dw-compare__badge--after">After</span>
      </div>
      <label class="dw-compare__range-label" for="hc-compare-range">
        <span class="dw-compare__sr-only">Drag to compare before and after</span>
        <input id="hc-compare-range" class="dw-compare__range" type="range" min="0" max="100" value="50" aria-label="Compare before and after" />
      </label>
    </div>
    <div class="hc-compare__stats">
      <div><strong>Up to 30%</strong><span>lower heating &amp; cooling costs with Low-E dual-pane glass*</span></div>
      <div><strong>~70%</strong><span>of project cost typically recouped at resale*</span></div>
      <div><strong>-50%</strong><span>outside noise with laminated glass option</span></div>
    </div>
    <p class="hc-footnote">*Industry averages (ENERGY STAR®, Remodeling Cost vs. Value). Actual results vary by home and glass package.</p>
  </div>
</section>

{{-- ============ REVIEWS ============ --}}
<section class="hc-section hc-reviews" aria-labelledby="hc-reviews-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--center">
      <span class="hc-eyebrow">Real reviews</span>
      <h2 id="hc-reviews-title" class="hc-h2">{{ $yelpCount }} Bay Area homeowners, {{ $yelpRating }} stars. Unedited.</h2>
    </div>
  </div>
  @include('partials.yelp-reviews', ['yelpShowHeading' => false, 'yelpInitialCount' => 6])
</section>

{{-- ============ GUARANTEE ============ --}}
<section class="hc-section hc-guarantee" aria-labelledby="hc-guarantee-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--center">
      <span class="hc-eyebrow hc-eyebrow--light">Our guarantee</span>
      <h2 id="hc-guarantee-title" class="hc-h2">Warranties written down, not promised on a handshake.</h2>
    </div>
    <div class="hc-guarantee__grid">
      <div class="hc-guarantee__card">
        <span class="hc-guarantee__big">Lifetime</span>
        <h3>Vinyl windows</h3>
        <p>Full lifetime <strong>transferable</strong> warranty on parts and labor — it stays with the house when you sell.</p>
      </div>
      <div class="hc-guarantee__card">
        <span class="hc-guarantee__big">20 yrs</span>
        <h3>Glass — wood clad, fiberglass, aluminum</h3>
        <p>Seal failure, fogging and Low-E coating defects covered for two decades.</p>
      </div>
      <div class="hc-guarantee__card">
        <span class="hc-guarantee__big">10 yrs</span>
        <h3>All other parts</h3>
        <p>Hardware, balances, weatherstripping and screens — plus the manufacturer’s own coverage on frame and glass.</p>
      </div>
    </div>
    <p class="hc-guarantee__note">Manufacturer’s warranty on glass and frame — lifetime on qualifying products. Full terms are included with your written quote.</p>
  </div>
</section>

{{-- ============ CERTIFICATIONS + SERVICE AREA ============ --}}
<section class="hc-section hc-area" aria-labelledby="hc-area-title">
  <div class="hc-container hc-area__grid">
    <div>
      <span class="hc-eyebrow">Certified &amp; local</span>
      <h2 id="hc-area-title" class="hc-h2">Factory-trained crews. One showroom in Burlingame. Nine counties served.</h2>
      <p class="hc-section__intro">Our installers are AAMA-certified and factory-trained by the brands below — the warranty is only as good as the install.</p>
      <div class="hc-cert-row">
        <img loading="lazy" src="/webflow-assets/images/6998617839debbabce241e8e_6915aaca08003de3e1e57018_marvin-logo-black.svg" alt="Marvin certified" />
        <img loading="lazy" src="/webflow-assets/images/69200ccf66431025ccaabea5_milgard.svg" alt="Milgard certified" />
        <img loading="lazy" src="/webflow-assets/images/69986178667bcb1013476512_6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg" alt="Andersen certified" />
      </div>
      <div class="hc-showroom">
        <strong>Showroom:</strong> {{ \App\Services\Seo\OrganizationSchema::showroomAddressLine() }} · Mon–Fri 8–6, Sat 9–3
      </div>
    </div>
    <div class="hc-area__counties">
      <h3 class="hc-h3">Where we work</h3>
      <ul class="hc-county-list">
        @foreach($counties as [$cName, $cSlug])
          <li><a href="/county-hub-pages/{{ $cSlug }}">{{ $cName }} County</a></li>
        @endforeach
      </ul>
      <a href="/gallery" class="hc-link">See recent installs near you →</a>
    </div>
  </div>
</section>

{{-- ============ FOR PROFESSIONALS ============ --}}
<section class="hc-section hc-pros" aria-labelledby="hc-pros-title">
  <div class="hc-container">
    <div class="hc-section__head hc-section__head--split">
      <div>
        <span class="hc-eyebrow">For professionals</span>
        <h2 id="hc-pros-title" class="hc-h2">Architects, contractors &amp; property managers: one accountable partner.</h2>
      </div>
      <p class="hc-section__intro">Spec support, multi-unit scheduling and turnkey install — with the capacity to run several jobs at once.</p>
    </div>
    <div class="hc-pro-grid">
      @foreach([
        ['/webflow-assets/images/home-professionals/icon-architects.png', 'Architects', 'Product specification, shop drawings and brand-agnostic recommendations from design through punch list.'],
        ['/webflow-assets/images/home-professionals/icon-contractors.png', 'Contractors', 'Measurements, ordering, delivery and certified installation on your schedule — backed by long-term warranties.'],
        ['/webflow-assets/images/home-professionals/icon-property-managers.png', 'Property managers', 'Occupied-unit replacements with clear scopes, tenant-friendly scheduling and one invoice.'],
      ] as [$pIcon, $pTitle, $pText])
        <div class="hc-pro-card">
          <x-img :src="$pIcon" preset="pro_icon" loading="lazy" :alt="$pTitle" width="64" height="64" />
          <h3 class="hc-h3">{{ $pTitle }}</h3>
          <p>{{ $pText }}</p>
        </div>
      @endforeach
    </div>
    <div class="hc-section__foot">
      <a href="/contacts" class="hc-btn hc-btn--outline">Talk to our commercial team</a>
    </div>
  </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="hc-section hc-faq" aria-labelledby="hc-faq-title">
  <div class="hc-container hc-faq__grid">
    <div>
      <span class="hc-eyebrow">Questions</span>
      <h2 id="hc-faq-title" class="hc-h2">The five things everyone asks first.</h2>
      <a href="/faq" class="hc-link">Read the full FAQ →</a>
    </div>
    <div class="hc-accordion">
      @foreach($faqs as $i => $faq)
        <details class="hc-accordion__item" {{ $i === 0 ? 'open' : '' }}>
          <summary>{{ $faq['q'] }}</summary>
          <p>{{ $faq['a'] }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ FINAL CTA ============ --}}
<section class="hc-section hc-final" aria-labelledby="hc-final-title">
  <div class="hc-container hc-final__card">
    <div class="hc-final__copy">
      <span class="hc-eyebrow hc-eyebrow--light">Your dream home starts here</span>
      <h2 id="hc-final-title" class="hc-h2">Tell us about your project. We’ll take care of the rest.</h2>
      <p>Free in-home or showroom consultation, written quote within 24 hours, {{ $discountLabel }} while the promotion lasts.</p>
      <div class="hc-hero__actions">
        <a href="#hc-quote" class="hc-btn hc-btn--primary">Get my free quote</a>
        <a href="tel:{{ $phoneTel }}" class="hc-btn hc-btn--ghost">Call {{ $phoneDisplay }}</a>
      </div>
    </div>
    <div class="hc-final__media">
      <x-img src="/webflow-assets/images/home-concept/home-concept-consult.jpg" preset="cta" loading="lazy" alt="Design consultation at the Deluxe Windows showroom" />
    </div>
  </div>
</section>

@endsection

@section('bodyScripts')
  <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script src="/webflow-assets/js/webflow.js" type="text/javascript"></script>
  @include('partials.attribution-tracking')
  @include('partials.visit-tracking')
  @include('partials.service-area-personalization')
  @include('partials.bing-phone-choice-modal')
  @include('partials.lead-form-scripts')
  @include('partials.phone-click-scripts')
  <script>
    (function () {
      var root = document.querySelector('[data-dw-compare]');
      if (!root) return;
      var viewport = root.querySelector('.dw-compare__viewport');
      var before = root.querySelector('[data-dw-compare-before]');
      var beforeImg = root.querySelector('.dw-compare__image--before');
      var divider = root.querySelector('[data-dw-compare-divider]');
      var range = root.querySelector('.dw-compare__range');
      var dragging = false;

      function sync() {
        if (beforeImg && viewport) {
          beforeImg.style.width = viewport.offsetWidth + 'px';
          beforeImg.style.height = viewport.offsetHeight + 'px';
        }
      }
      function set(p) {
        p = Math.max(0, Math.min(100, p));
        before.style.width = p + '%';
        divider.style.left = p + '%';
        range.value = String(Math.round(p));
      }
      function fromPointer(x) {
        var r = viewport.getBoundingClientRect();
        if (r.width > 0) set(((x - r.left) / r.width) * 100);
      }
      range.addEventListener('input', function () { set(Number(range.value)); });
      viewport.addEventListener('pointerdown', function (e) { dragging = true; viewport.setPointerCapture(e.pointerId); fromPointer(e.clientX); });
      viewport.addEventListener('pointermove', function (e) { if (dragging) fromPointer(e.clientX); });
      viewport.addEventListener('pointerup', function () { dragging = false; });
      viewport.addEventListener('pointercancel', function () { dragging = false; });
      set(50); sync();
      if (typeof ResizeObserver !== 'undefined') new ResizeObserver(sync).observe(viewport);
      else window.addEventListener('resize', sync);

      // Smooth scroll to quote card + focus first field.
      document.querySelectorAll('a[href="#hc-quote"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
          var card = document.getElementById('hc-quote');
          if (!card) return;
          e.preventDefault();
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
          var first = card.querySelector('input[name="Name"]');
          if (first) setTimeout(function () { first.focus({ preventScroll: true }); }, 450);
        });
      });
    })();
  </script>
@endsection
