@php
  /**
   * Redesigned mobile hero (variant "new"): consultation-first, no discount badge.
   * Rendered on every hero page; CSS keeps it mobile-only (<= 991px).
   */
  $areaLabel = trim((string) ($heroNewAreaLabel ?? '')) !== '' ? $heroNewAreaLabel : 'Bay Area';
  $eyebrow = trim((string) ($heroNewEyebrow ?? '')) !== ''
    ? $heroNewEyebrow
    : 'Window & Door Replacement · '.$areaLabel;
  $titleLead = trim((string) ($heroNewTitleLead ?? '')) !== ''
    ? $heroNewTitleLead
    : 'The same crew has been doing this for';
  $titleAccent = $heroNewTitleAccent ?? 'thirty years.';
  $titleAccent = trim((string) $titleAccent);
  $sub = trim((string) ($heroNewSub ?? '')) !== ''
    ? $heroNewSub
    : 'Marvin, Western Window Systems and Andersen — measured and fitted by our own people. No subcontractors.';
  $countyLabel = trim((string) ($heroNewCountyLabel ?? '')) !== ''
    ? $heroNewCountyLabel
    : 'In the trade, San Mateo County';
  $facts = $heroNewFacts ?? [
    ['value' => '30 yrs', 'note' => $countyLabel],
    ['value' => 'In-house', 'note' => 'Own crews — never subcontracted'],
    ['value' => 'Factory trained', 'note' => 'AAMA certified installers'],
    ['value' => '0.27', 'note' => 'Every window meets Title 24'],
  ];
  $phoneTel = $heroNewPhoneTel ?? ($localPhone['phone_tel'] ?? site_phone_tel());
  $phoneDisplay = $heroNewPhoneDisplay ?? ($localPhone['phone_display'] ?? site_phone_display());
  $cityPlaceholder = trim((string) ($heroNewCityPlaceholder ?? '')) !== ''
    ? $heroNewCityPlaceholder
    : 'San Francisco';
@endphp

<div class="hero-nm" data-hero-new>
  <p class="hero-nm__eyebrow"><span class="hero-nm__eyebrow-rule" aria-hidden="true"></span>{{ $eyebrow }}</p>

  {{-- Deliberately not a heading: the page keeps the H1 it already had, so the
       A/B test changes layout and copy without touching the SEO outline. --}}
  <p class="hero-nm__title">
    {{ $titleLead }}@if($titleAccent !== '') <span class="hero-nm__title-accent">{{ $titleAccent }}</span>@endif
  </p>

  <p class="hero-nm__sub">{{ $sub }}</p>

  <div class="hero-nm__actions">
    <button type="button" class="hero-nm__cta hero-nm__cta--book" data-open-estimate-modal>
      Book a consultation
    </button>
    <a
      href="tel:{{ $phoneTel }}"
      class="hero-nm__cta hero-nm__cta--call"
      data-area-phone
      data-phone-source="hero-new-mobile"
      aria-label="Call {{ $phoneDisplay }}"
    >
      Call <span data-area-phone-number>{{ $phoneDisplay }}</span>
    </a>
  </div>

  <div class="hero-nm__facts">
    @foreach($facts as $fact)
      <div class="hero-nm__fact">
        <p class="hero-nm__fact-value">{{ $fact['value'] }}</p>
        <p class="hero-nm__fact-note">{{ $fact['note'] }}</p>
      </div>
    @endforeach
  </div>

  <div class="hero-nm__form-card">
    <p class="hero-nm__form-title">Book your in-home consultation</p>
    <p class="hero-nm__form-copy">
      We measure, walk the openings, and give you a written price that holds. No coupons, no pressure.
    </p>

    <div class="hero-nm__form-wrap w-form">
      <form
        id="wf-form-Hero-New-Mobile"
        name="wf-form-Hero-New-Mobile"
        method="get"
        class="hero-nm__form js-laravel-lead-form"
        data-form-id="hero-new-mobile"
      >
        <label class="hero-nm__label" for="hero-nm-name">Full name*</label>
        <input
          id="hero-nm-name"
          class="hero-nm__input"
          type="text"
          name="Name"
          data-name="Name"
          maxlength="256"
          autocomplete="name"
          placeholder="Full name"
          required
        />

        <label class="hero-nm__label" for="hero-nm-phone">Phone*</label>
        <input
          id="hero-nm-phone"
          class="hero-nm__input"
          type="tel"
          name="Phone"
          data-name="Phone"
          maxlength="256"
          autocomplete="tel"
          inputmode="tel"
          placeholder="{{ $phoneDisplay }}"
          required
        />

        <label class="hero-nm__label" for="hero-nm-email">Email*</label>
        <input
          id="hero-nm-email"
          class="hero-nm__input"
          type="email"
          name="Email"
          data-name="Email"
          maxlength="256"
          autocomplete="email"
          placeholder="example@email.com"
          required
        />

        <label class="hero-nm__label" for="hero-nm-city">City</label>
        <input
          id="hero-nm-city"
          class="hero-nm__input"
          type="text"
          name="Subject"
          data-name="Subject"
          maxlength="256"
          placeholder="{{ $cityPlaceholder }}"
        />

        <button type="submit" class="hero-nm__submit" data-wait="Please wait...">
          Book consultation
        </button>

        <p class="hero-nm__form-note">
          Title 24 rules tightened again — we size every unit to the current requirement.
        </p>
      </form>

      <div class="w-form-done" tabindex="-1" role="region" aria-label="Consultation request success">
        <div>Thank you! We will call you to confirm the visit.</div>
      </div>
      <div class="w-form-fail" tabindex="-1" role="region" aria-label="Consultation request failure">
        <div>Oops! Something went wrong while submitting the form.</div>
      </div>
    </div>
  </div>
</div>
