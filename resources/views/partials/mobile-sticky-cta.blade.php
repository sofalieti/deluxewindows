@once
@php
  $stickyPhoneTel = site_phone_tel();
  $stickyPhoneDisplay = site_phone_display();
@endphp

<div class="mobile-sticky-cta" data-mobile-sticky-cta>
  <a
    href="tel:{{ $stickyPhoneTel }}"
    class="mobile-sticky-cta__btn mobile-sticky-cta__btn--call"
    data-area-phone
    data-phone-source="mobile-sticky-cta"
    aria-label="Call {{ $stickyPhoneDisplay }}"
  >
    Call now
  </a>
  <button type="button" class="mobile-sticky-cta__btn mobile-sticky-cta__btn--book" data-open-estimate-modal>
    Book consultation
  </button>
</div>
@endonce
