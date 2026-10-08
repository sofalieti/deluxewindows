@extends('layouts.classic')

@section('bodyClass', 'body-18 height-auto referral-landing-page referral-invite-page')

@section('head')
  @php
    $refCss = public_path('webflow-overrides/referral-landing.css');
    $refCssVersion = is_file($refCss) ? (string) filemtime($refCss) : '1';
  @endphp
  <link href="/webflow-overrides/referral-landing.css?v={{ $refCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
  $first = $partner->firstName();
  $formId = 'Referral Invite Form';
@endphp

<section class="rf-hero rf-hero--invite" aria-labelledby="rf-invite-heading">
  <div class="rf-hero__media" aria-hidden="true">
    <img src="/webflow-assets/images/new-construction/after-with-windows.avif" alt="" width="1920" height="1080" fetchpriority="high" decoding="async" />
  </div>
  <div class="w-layout-blockcontainer container-default w-container rf-hero__grid">
    <div class="rf-hero__copy">
      <p class="rf-invite-from">
        <span class="rf-invite-from__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($first, 0, 1)) }}</span>
        <span><b>{{ $first }}</b> recommends Deluxe Windows</span>
      </p>
      <h1 id="rf-invite-heading" class="rf-hero__title">You've got ${{ $friendCredit }} off new windows &amp; doors</h1>
      <p class="rf-hero__lead">
        {{ $first }} thought you'd want an honest, no-pressure quote from a Bay Area installer they trust.
        Book a free in-home estimate — your <strong>${{ $friendCredit }} referral credit</strong> is already attached.
      </p>
      <ul class="rf-hero__chips">
        <li>Free in-home estimate</li>
        <li>{{ $yelpRating }}★ · {{ $yelpCount }} Yelp reviews</li>
        <li>30+ years · employee-owned</li>
      </ul>
      <p class="rf-hero__call">Prefer to talk? <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a> — mention {{ $first }}.</p>
    </div>

    <div class="rf-invite-form" id="estimate">
      <div class="rf-invite-form__ticket">
        <span>Referral credit</span>
        <b>−${{ $friendCredit }}</b>
        <small>from {{ $first }} · applied to your final invoice</small>
      </div>
      <div class="w-form rf-invite-form__wrap">
        <form id="wf-form-Referral-Invite" name="wf-form-Referral-Invite" method="get" class="rf-form" data-form-id="{{ $formId }}" aria-label="Request a free estimate">
          <input type="hidden" name="Form ID" value="{{ $formId }}" />
          <div class="rf-field">
            <label for="rf-inv-name">Full name*</label>
            <input id="rf-inv-name" type="text" name="Name" data-name="Name" autocomplete="name" required maxlength="256" placeholder="Full name" />
          </div>
          <div class="rf-field">
            <label for="rf-inv-phone">Phone*</label>
            <input id="rf-inv-phone" type="tel" name="Phone" data-name="Phone" autocomplete="tel" inputmode="tel" required maxlength="256" placeholder="{{ site_phone_display() }}" />
          </div>
          <div class="rf-field">
            <label for="rf-inv-email">Email <span>(optional)</span></label>
            <input id="rf-inv-email" type="email" name="Email" data-name="Email" autocomplete="email" maxlength="256" placeholder="you@email.com" />
          </div>
          <div class="rf-field">
            <label for="rf-inv-city">City</label>
            <input id="rf-inv-city" type="text" name="Subject" data-name="Subject" autocomplete="address-level2" maxlength="256" placeholder="San Mateo" />
          </div>
          <div class="rf-field">
            <label for="rf-inv-msg">What are you thinking about? <span>(optional)</span></label>
            <input id="rf-inv-msg" type="text" name="Message" data-name="Message" maxlength="5000" placeholder="e.g. 8 windows + a patio door" />
          </div>
          <button type="submit" class="rf-btn rf-btn--accent rf-btn--block" data-wait="Please wait...">Claim my ${{ $friendCredit }} &amp; book estimate</button>
          <p class="rf-form__fine">A real person calls within one business day. No obligation, no pressure.</p>
        </form>
        <div class="w-form-done rf-alert rf-alert--ok" tabindex="-1" role="region" aria-label="Form success" data-keep-done>
          Done! Your ${{ $friendCredit }} credit is saved. We'll call you within one business day to set up the estimate.
        </div>
        <div class="w-form-fail rf-alert rf-alert--err" tabindex="-1" role="region" aria-label="Form failure">
          Something went wrong. Please call <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a> and mention {{ $first }}.
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="rf-head">
      <p class="rf-kicker">What happens next</p>
      <h2 class="display-8 mid">Three simple steps</h2>
    </div>
    <ol class="rf-steps rf-steps--three">
      <li class="rf-step"><span class="rf-step__n">1</span><h3>Free estimate</h3><p>A specialist visits (or you stop by our Burlingame showroom), measures every opening and shows real samples.</p></li>
      <li class="rf-step"><span class="rf-step__n">2</span><h3>Written quote</h3><p>Itemized by opening — brand, glass, hardware, labor — with your ${{ $friendCredit }} referral credit on it.</p></li>
      <li class="rf-step"><span class="rf-step__n">3</span><h3>Install day</h3><p>Most homes are done in 1–2 days by our own AAMA-certified crew. Old units hauled away.</p></li>
    </ol>
  </div>
</section>

<section class="section rf-section rf-section--tint">
  <div class="w-layout-blockcontainer container-default w-container rf-why">
    <div class="rf-why__intro">
      <p class="rf-kicker">Why {{ $first }} sent you here</p>
      <h2 class="display-8 mid">A contractor neighbors actually recommend</h2>
      <a href="/windows" class="rf-link">Browse windows &amp; prices →</a>
    </div>
    <ul class="rf-why__grid">
      <li><b>30+ years</b><span>Installing windows and doors across the Bay Area</span></li>
      <li><b>100% employee-owned</b><span>The people at your house own the company</span></li>
      <li><b>11 brands</b><span>Marvin, Andersen, Milgard, Anlin and more — honest comparison</span></li>
      <li><b>Own crews</b><span>AAMA-certified, never subcontracted</span></li>
      <li><b>Lifetime warranty</b><span>Transferable on vinyl windows, parts and labor</span></li>
      <li><b>Financing</b><span>Monthly plans, including 0% options for qualified buyers</span></li>
    </ul>
  </div>
</section>

<section class="section rf-section">
  <div class="w-layout-blockcontainer container-default w-container rf-invite-cta">
    <div>
      <h2 class="display-8 mid">Ready when you are</h2>
      <p>Your ${{ $friendCredit }} credit is attached to this page. Book now or call and mention {{ $first }}.</p>
    </div>
    <div class="rf-invite-cta__actions">
      <a href="#estimate" class="rf-btn rf-btn--accent">Book my free estimate</a>
      <a href="tel:{{ site_phone_tel() }}" class="rf-btn rf-btn--outline">Call {{ site_phone_display() }}</a>
    </div>
    <p class="rf-invite-cta__earn">Love your new windows later? <a href="/referrals">Refer a neighbor and earn ${{ $reward }}</a>.</p>
  </div>
</section>
@endsection
