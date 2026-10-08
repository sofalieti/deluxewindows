@extends('layouts.classic')

@section('bodyClass', 'body-18 height-auto referral-landing-page')

@section('head')
  @php
    $refCss = public_path('webflow-overrides/referral-landing.css');
    $refCssVersion = is_file($refCss) ? (string) filemtime($refCss) : '1';
  @endphp
  <link href="/webflow-overrides/referral-landing.css?v={{ $refCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
  $payoutList = implode(', ', array_slice($payoutMethods, 0, -1)).' or '.end($payoutMethods);
  $faqs = [
    ['Is this a real cash reward?', "Yes. You get \${$reward} sent by {$payoutList} {$payoutWindow} after your referral's installation is complete. No points, no store credit."],
    ['What does my friend get?', "\${$friendCredit} off their window or door project, applied to the final invoice — plus a free in-home estimate and a written, itemized quote. They don't have to do anything special: the credit is attached automatically when they come through your link or QR code."],
    ['What counts as a successful referral?', "A new customer (not already working with us) who signs a contract and has their windows or doors installed by Deluxe Windows. If the project doesn't move forward, nobody owes anybody anything — and your friend still got honest advice for free."],
    ['What if my friend calls instead of using the link?', "Calls from your link and QR code are tracked automatically. If they dial us directly, they just mention your name — we'll attach the referral by hand."],
    ['Is there a limit?', "No cap. Refer one neighbor or twenty — every installed project pays \${$reward}."],
    ['Do I have to be a Deluxe Windows customer?', 'No. Past customers make great partners, but so do realtors, contractors, designers, property managers and anyone who knows Bay Area homeowners.'],
    ['Taxes?', "If you earn \$600 or more from us in a calendar year, we'll ask for a W-9 and send a 1099 — that's an IRS rule, not ours."],
  ];
@endphp

{{-- 1. Hero: the whole offer in one line --}}
<section class="rf-hero" aria-labelledby="rf-hero-heading">
  <div class="rf-hero__media" aria-hidden="true">
    <img src="/webflow-assets/images/new-construction/after-with-windows.avif" alt="" width="1920" height="1080" fetchpriority="high" decoding="async" />
  </div>
  <div class="w-layout-blockcontainer container-default w-container rf-hero__grid">
    <div class="rf-hero__copy">
      <p class="rf-kicker rf-kicker--light">Deluxe Windows Referral Program · Bay Area</p>
      <h1 id="rf-hero-heading" class="rf-hero__title">Give ${{ $friendCredit }}. Get ${{ $reward }}.</h1>
      <p class="rf-hero__lead">
        Know a neighbor who needs new windows or doors? Share your personal link.
        <strong>They get ${{ $friendCredit }} off</strong> their project — <strong>you get ${{ $reward }}</strong>
        via {{ $payoutList }} once it's installed.
      </p>
      <div class="rf-hero__actions">
        <a href="#apply" class="rf-btn rf-btn--accent">Get my referral link</a>
        <a href="#how" class="rf-btn rf-btn--ghost">How it works</a>
      </div>
      <ul class="rf-hero__chips">
        <li>No fine print</li>
        <li>No limit on referrals</li>
        <li>Paid {{ $payoutWindow }} after install</li>
      </ul>
    </div>

    <div class="rf-hero__card" aria-hidden="true">
      <div class="rf-split">
        <div class="rf-split__side">
          <span class="rf-split__who">Your neighbor</span>
          <b class="rf-split__amount">−${{ $friendCredit }}</b>
          <span class="rf-split__what">off their window project</span>
        </div>
        <div class="rf-split__plus">+</div>
        <div class="rf-split__side rf-split__side--you">
          <span class="rf-split__who">You</span>
          <b class="rf-split__amount">${{ $reward }}</b>
          <span class="rf-split__what">sent via {{ $payoutMethods[0] ?? 'Zelle' }}</span>
        </div>
      </div>
      <div class="rf-notify">
        <span class="rf-notify__icon">$</span>
        <span class="rf-notify__text"><b>{{ $payoutMethods[0] ?? 'Zelle' }}</b><span>Deluxe Windows sent you ${{ $reward }}.00</span><small>“Thanks for the referral — the Smiths love their new windows.”</small></span>
      </div>
    </div>
  </div>
</section>

{{-- 2. Why it works for everyone --}}
<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-head">
      <p class="rf-kicker">Win · win · win</p>
      <h2 class="display-8 mid">Why everybody comes out ahead</h2>
      <p class="rf-head__lead">You're not “selling” anyone. You're handing a neighbor a discount and a contractor you trust — and getting thanked for it.</p>
    </div>
    <div class="rf-wins">
      <article class="rf-win">
        <figure class="rf-win__media"><img src="/webflow-assets/images/home-concept/home-concept-hero.jpg" alt="Bay Area home with new windows at dusk" width="1024" height="576" loading="lazy" decoding="async" /></figure>
        <span class="rf-win__tag">You</span>
        <h3>${{ $reward }} for each installed project</h3>
        <ul>
          <li>Real money via {{ $payoutList }} — not points</li>
          <li>No cap: three neighbors = ${{ $reward * 3 }}</li>
          <li>Watch every visit, lead and payout in your dashboard</li>
        </ul>
      </article>
      <article class="rf-win rf-win--featured">
        <figure class="rf-win__media"><img src="/webflow-assets/images/home-concept/home-concept-consult.jpg" alt="Homeowners comparing window frame samples with a Deluxe Windows specialist" width="1024" height="768" loading="lazy" decoding="async" /></figure>
        <span class="rf-win__tag">Your neighbor</span>
        <h3>${{ $friendCredit }} off + a contractor you vouched for</h3>
        <ul>
          <li>${{ $friendCredit }} credit on the final invoice</li>
          <li>Free in-home estimate, written itemized quote</li>
          <li>Honest advice across 11 brands — no house brand to push</li>
        </ul>
      </article>
      <article class="rf-win">
        <figure class="rf-win__media"><img src="/webflow-assets/images/home-concept/home-concept-after.jpg" alt="Single-story home with new black-frame windows and entry door" width="1024" height="576" loading="lazy" decoding="async" /></figure>
        <span class="rf-win__tag">Your block</span>
        <h3>Quieter, warmer, better-looking homes</h3>
        <ul>
          <li>Dual-pane Low-E glass that meets Title 24</li>
          <li>Lower energy bills, less street noise</li>
          <li>Curb appeal that lifts the whole street</li>
        </ul>
      </article>
    </div>
  </div>
</section>

{{-- 3. How it works --}}
<section class="section rf-section rf-section--tint" id="how">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-head">
      <p class="rf-kicker">How it works</p>
      <h2 class="display-8 mid">Four steps. Two minutes of your time.</h2>
    </div>
    <ol class="rf-steps">
      <li class="rf-step">
        <span class="rf-step__n">1</span>
        <h3>Join</h3>
        <p>Fill out the short form below. We set up your partner account and email your login.</p>
      </li>
      <li class="rf-step">
        <span class="rf-step__n">2</span>
        <h3>Grab your kit</h3>
        <p>Your personal link, a QR code, and print-ready posters, flyers and cards — all generated for you.</p>
      </li>
      <li class="rf-step">
        <span class="rf-step__n">3</span>
        <h3>Share</h3>
        <p>Text it, post it on Nextdoor, drop it in Instagram stories, or pin a poster in your building lobby.</p>
      </li>
      <li class="rf-step">
        <span class="rf-step__n">4</span>
        <h3>Get paid</h3>
        <p>After your neighbor's install is complete, we send ${{ $reward }} {{ $payoutWindow }}. Every step is visible in your dashboard.</p>
      </li>
    </ol>
  </div>
</section>

{{-- 4. Why neighbors will thank you --}}
<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container rf-why">
    <div class="rf-why__intro">
      <p class="rf-kicker">Easy to recommend</p>
      <h2 class="display-8 mid">Why your neighbors will thank you</h2>
      <p class="rf-head__lead">A referral is only worth sharing if the company makes you look good. Here's what your neighbor gets with us.</p>
      <a href="/about" class="rf-link">More about Deluxe Windows →</a>
      <figure class="rf-why__photo">
        <img src="/webflow-assets/images/home-concept/home-concept-installer.jpg" alt="Deluxe Windows installer fitting a new vinyl window" width="1024" height="768" loading="lazy" decoding="async" />
        <figcaption>Our own crew — never subcontracted</figcaption>
      </figure>
    </div>
    <ul class="rf-why__grid">
      <li><b>30+ years</b><span>Installing windows and doors across the Bay Area</span></li>
      <li><b>100% employee-owned</b><span>The people at the house own the company</span></li>
      <li><b>{{ $yelpRating }}★ on Yelp</b><span>{{ $yelpCount }} reviews from Bay Area homeowners</span></li>
      <li><b>Our own crews</b><span>AAMA-certified installers — never subcontracted</span></li>
      <li><b>11 brands</b><span>Marvin, Andersen, Milgard, Anlin and more — we fit the house, not a quota</span></li>
      <li><b>1–2 day installs</b><span>Most homes done fast, clean, with old units hauled away</span></li>
      <li><b>Lifetime warranty</b><span>Transferable on vinyl windows, parts and labor</span></li>
      <li><b>Financing</b><span>Monthly plans, including 0% options for qualified buyers</span></li>
    </ul>
  </div>
</section>

{{-- 5. Earnings calculator --}}
<section class="section rf-section rf-section--dark" aria-labelledby="rf-calc-heading">
  <div class="w-layout-blockcontainer container-default w-container rf-calc" data-rf-calc data-reward="{{ $reward }}" data-credit="{{ $friendCredit }}">
    <div>
      <p class="rf-kicker rf-kicker--light">Do the math</p>
      <h2 id="rf-calc-heading" class="rf-calc__title">How many neighbors are thinking about new windows?</h2>
      <label class="rf-calc__label" for="rf-calc-range">Installed referrals this year: <b data-rf-calc-count>3</b></label>
      <input id="rf-calc-range" class="rf-calc__range" type="range" min="1" max="20" value="3" />
    </div>
    <div class="rf-calc__results">
      <div><span>You earn</span><b data-rf-calc-earn>${{ $reward * 3 }}</b></div>
      <div><span>Your neighbors save</span><b data-rf-calc-save>${{ $friendCredit * 3 }}</b></div>
    </div>
  </div>
</section>

{{-- 6. Partner toolkit --}}
<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container rf-kit">
    <div class="rf-kit__copy">
      <p class="rf-kicker">Your partner kit</p>
      <h2 class="display-8 mid">Everything you need to share — ready in your account</h2>
      <ul class="rf-checks">
        <li><b>Personal link</b> — one tap to copy and send</li>
        <li><b>QR code</b> — download as PNG or SVG</li>
        <li><b>Print-ready poster, flyers and cards</b> — US Letter, with your QR already on it</li>
        <li><b>Ready-made posts</b> for Nextdoor, Instagram, text and email</li>
        <li><b>Live dashboard</b> — visits, leads, quotes, installs and payouts, broken down by channel</li>
      </ul>
      <a href="#apply" class="rf-btn rf-btn--primary">Get my kit</a>
    </div>
    <div class="rf-kit__poster" aria-hidden="true">
      <div class="rf-poster">
        <span class="rf-poster__eyebrow">Hey neighbors!</span>
        <b class="rf-poster__title">New windows?<br />Get ${{ $friendCredit }} off.</b>
        <span class="rf-poster__qr" data-rf-qr="{{ url('/referrals') }}"></span>
        <span class="rf-poster__cta">Scan for the details</span>
        <span class="rf-poster__by">Recommended by Alex · Deluxe Windows</span>
      </div>
    </div>
  </div>
</section>

{{-- 7. Where to share + who it's for --}}
<section class="section rf-section rf-section--tint">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-head">
      <p class="rf-kicker">Where it works best</p>
      <h2 class="display-8 mid">Share it where neighbors already ask for recommendations</h2>
    </div>
    <div class="rf-channels">
      <article>
        <span class="rf-channels__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v9.5h13V10"/><path d="M10 19.5v-5h4v5"/></svg></span>
        <h3>Nextdoor</h3><p>“Anyone know a good window contractor?” comes up every week. Reply with your link.</p>
      </article>
      <article>
        <span class="rf-channels__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 5.5h16v10H9l-5 4z"/><path d="M8 9.5h8M8 12.5h5"/></svg></span>
        <h3>Text &amp; group chats</h3><p>The highest-converting channel. One message to the friend who just bought a house.</p>
      </article>
      <article>
        <span class="rf-channels__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="4.5"/><circle cx="12" cy="12" r="3.6"/><circle cx="16.6" cy="7.4" r=".6"/></svg></span>
        <h3>Instagram &amp; Facebook</h3><p>Before/after of your own windows + your link in stories. Neighbors trust real homes.</p>
      </article>
      <article>
        <span class="rf-channels__icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h2.5v2.5H14zM17.5 17.5H20V20h-2.5zM14 20h2M20 14v1.5"/></svg></span>
        <h3>Posters with QR</h3><p>Building lobbies, HOA boards, coffee shops, hardware stores — places people wait and have a phone in hand.</p>
      </article>
    </div>
    <div class="rf-who">
      <h3>Great fit for</h3>
      <ul>
        <li>Past customers</li>
        <li>Realtors &amp; stagers</li>
        <li>Contractors &amp; handymen</li>
        <li>Interior designers</li>
        <li>Property managers &amp; HOA boards</li>
        <li>Local creators &amp; community pages</li>
      </ul>
    </div>
  </div>
</section>

{{-- 8. Rules, no fine print --}}
<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-rules">
      <p class="rf-kicker">The rules · no fine print</p>
      <h2 class="display-8 mid">Everything, in plain English</h2>
      <ol>
        <li><b>${{ $reward }} to you</b> for every new customer whose project is <b>installed</b> after coming through your link, QR code, or mentioning your name.</li>
        <li><b>${{ $friendCredit }} off for your neighbor</b>, applied to their final invoice.</li>
        <li><b>Paid {{ $payoutWindow }}</b> after installation, via {{ $payoutList }}.</li>
        <li><b>No cap.</b> Every installed referral pays.</li>
        <li>Self-referrals and people already working with us don't count.</li>
        <li>Partner accounts are reviewed by a person, so spam stays out and payouts stay fast.</li>
      </ol>
    </div>
  </div>
</section>

{{-- 9. FAQ --}}
<section class="section rf-section rf-section--tint">
  <div class="w-layout-blockcontainer container-default w-container rf-faq">
    <div class="rf-head">
      <p class="rf-kicker">FAQ</p>
      <h2 class="display-8 mid">Questions partners ask</h2>
    </div>
    <div class="rf-faq__list">
      @foreach($faqs as $i => [$q, $a])
        <details @if($i === 0) open @endif>
          <summary>{{ $q }}</summary>
          <p>{{ $a }}</p>
        </details>
      @endforeach
    </div>
  </div>
</section>

{{-- 10. Apply --}}
<section class="section rf-section rf-apply-section" id="apply" aria-labelledby="referral-apply-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-apply">
      <div class="rf-apply__intro">
        <p class="rf-kicker">Join the program</p>
        <h2 id="referral-apply-heading" class="display-8 mid">Get your link, QR code &amp; posters</h2>
        <p>Tell us who you are. Once your account is approved you'll get a login to your partner dashboard with your personal link and print-ready materials.</p>
        <ul class="rf-checks rf-checks--compact">
          <li>Free to join, no obligations</li>
          <li>${{ $reward }} per installed referral, no cap</li>
          <li>${{ $friendCredit }} off for every neighbor you send</li>
        </ul>
        <p class="rf-apply__login">Already a partner? <a href="{{ route('platform.referral.my-dashboard') }}">Log in to your dashboard</a></p>
      </div>

      @if(session('referral_application_success'))
        <div class="rf-alert rf-alert--ok" role="status">
          Thanks — we received your application. We'll review it and email your login and referral kit.
        </div>
      @else
        <form method="post" action="{{ route('referrals.apply') }}" class="rf-form" data-no-lead>
          @csrf
          <div class="rf-field">
            <label for="referral_full_name">Full name</label>
            <input id="referral_full_name" type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="255" autocomplete="name" />
          </div>
          <div class="rf-field">
            <label for="referral_email">Email</label>
            <input id="referral_email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" />
          </div>
          <div class="rf-field">
            <label for="referral_phone">Phone <span>(optional)</span></label>
            <input id="referral_phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="50" autocomplete="tel" />
          </div>
          <div class="rf-field">
            <label for="referral_message">How will you share? <span>(optional)</span></label>
            <textarea id="referral_message" name="message" rows="3" maxlength="5000" placeholder="e.g. I'm a realtor in San Mateo / past customer / HOA board member">{{ old('message') }}</textarea>
          </div>

          @if($errors->any())
            <div class="rf-alert rf-alert--err" role="alert">{{ $errors->first() }}</div>
          @endif

          <button type="submit" class="rf-btn rf-btn--accent rf-btn--block">Join the referral program</button>
          <p class="rf-form__fine">{{ $license }} · We never sell your info.</p>
        </form>
        <script>
          (function () {
            var data;
            try { data = JSON.parse(sessionStorage.getItem('dwReferralPrefill') || 'null'); } catch (e) { data = null; }
            if (!data) return;
            ['full_name', 'email', 'phone'].forEach(function (key) {
              var input = document.getElementById('referral_' + key);
              if (input && !input.value && typeof data[key] === 'string') input.value = data[key];
            });
          })();
        </script>
      @endif
    </div>
  </div>
</section>

<script>
  (function () {
    var root = document.querySelector('[data-rf-calc]');
    if (!root) return;
    var range = root.querySelector('input[type="range"]');
    var reward = parseInt(root.getAttribute('data-reward'), 10) || 0;
    var credit = parseInt(root.getAttribute('data-credit'), 10) || 0;
    function money(n) { return '$' + n.toLocaleString('en-US'); }
    function update() {
      var n = parseInt(range.value, 10) || 0;
      root.querySelector('[data-rf-calc-count]').textContent = n;
      root.querySelector('[data-rf-calc-earn]').textContent = money(n * reward);
      root.querySelector('[data-rf-calc-save]').textContent = money(n * credit);
    }
    range.addEventListener('input', update);
    update();
  })();
</script>
<script src="/js/referral-qr.js?v={{ filemtime(public_path('js/referral-qr.js')) }}" defer></script>
<script>
  window.addEventListener('DOMContentLoaded', function () {
    if (window.DeluxeReferralQr) window.DeluxeReferralQr.render(document.querySelector('.rf-kit__poster'));
  });
</script>
@endsection
