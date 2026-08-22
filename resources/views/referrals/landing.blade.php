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
<section class="section hero-v4 page-intro-hero referral-hero">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-vflex inner-container _500px---mbl center">
      <div class="mg-top-small">
        <p class="referral-kicker">Referral Partner Program</p>
        <h1 class="display-10 mid text-light">Earn $150 for Every Customer You Refer</h1>
      </div>
    </div>
    <div class="mg-top-small">
      <div class="inner-container _690px center">
        <div class="text-neutral-light">
          <p class="paragraph-26">
            Share Deluxe Windows with homeowners in the Bay Area. When your referral becomes a real customer,
            you earn <strong>$150</strong>. Apply below — we’ll review your request and send login details.
          </p>
        </div>
        <div class="referral-hero-actions">
          <a href="#apply" class="primary-button w-inline-block">
            <div class="text-block-22">Apply to Become a Partner</div>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="text-center---mbl">
      <h2 class="display-8 mid">How It Works</h2>
    </div>
    <div class="mg-top-large">
      <div class="referral-steps" role="list">
        <article class="referral-step" role="listitem">
          <span class="referral-step-number" aria-hidden="true">01</span>
          <h3>Apply</h3>
          <p>Tell us who you are. We review applications and create your partner account.</p>
        </article>
        <article class="referral-step" role="listitem">
          <span class="referral-step-number" aria-hidden="true">02</span>
          <h3>Get your link</h3>
          <p>Log in to the partner portal and copy your unique referral URL.</p>
        </article>
        <article class="referral-step" role="listitem">
          <span class="referral-step-number" aria-hidden="true">03</span>
          <h3>Share it</h3>
          <p>Send the link to friends, clients, or your audience. We track visits and leads automatically.</p>
        </article>
        <article class="referral-step" role="listitem">
          <span class="referral-step-number" aria-hidden="true">04</span>
          <h3>Earn $150</h3>
          <p>When a referred lead becomes a sold customer and we approve the payout, you get $150.</p>
        </article>
      </div>
    </div>
  </div>
</section>

<section class="section top-none">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="referral-rules">
      <h2 class="display-8 mid">Program Rules</h2>
      <ul>
        <li>Reward is <strong>$150 per sold customer</strong> attributed to your referral link.</li>
        <li>Payouts are confirmed by our team after the job is sold — not automatic on form submit.</li>
        <li>Self-referrals and spam traffic are not eligible.</li>
        <li>Partner accounts are approved manually; there is no public self-registration.</li>
        <li>Track visits, leads, and reward status anytime in your partner dashboard.</li>
      </ul>
    </div>
  </div>
</section>

<section class="section top-none" id="apply" aria-labelledby="referral-apply-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="referral-apply-shell">
      <div>
        <span class="referral-kicker">Join the program</span>
        <h2 id="referral-apply-heading" class="display-8 mid">Apply for a Partner Account</h2>
        <p class="referral-apply-copy">
          Submit your details. After approval you’ll receive login credentials for the partner portal
          and your personal referral link.
        </p>
      </div>

      @if(session('referral_application_success'))
        <div class="referral-apply-success" role="status">
          Thanks — we received your application. We’ll review it and email you next steps.
        </div>
      @else
        <form method="post" action="{{ route('referrals.apply') }}" class="referral-apply-form">
          @csrf
          <label class="referral-label" for="referral_full_name">Full name</label>
          <input id="referral_full_name" class="referral-input" type="text" name="full_name" value="{{ old('full_name') }}" required maxlength="255" />

          <label class="referral-label" for="referral_email">Email</label>
          <input id="referral_email" class="referral-input" type="email" name="email" value="{{ old('email') }}" required maxlength="255" />

          <label class="referral-label" for="referral_phone">Phone <span class="referral-optional">(optional)</span></label>
          <input id="referral_phone" class="referral-input" type="tel" name="phone" value="{{ old('phone') }}" maxlength="50" />

          <label class="referral-label" for="referral_message">About you <span class="referral-optional">(optional)</span></label>
          <textarea id="referral_message" class="referral-input referral-textarea" name="message" rows="4" maxlength="5000">{{ old('message') }}</textarea>

          @if($errors->any())
            <div class="referral-apply-error" role="alert">
              {{ $errors->first() }}
            </div>
          @endif

          <button type="submit" class="primary-button w-inline-block referral-submit">
            <div class="text-block-22">Submit Application</div>
          </button>
        </form>
      @endif
    </div>
  </div>
</section>
@endsection
