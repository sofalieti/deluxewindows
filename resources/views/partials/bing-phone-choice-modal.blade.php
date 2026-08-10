@once
<div
  id="bingPhoneChoiceModal"
  class="bing-phone-choice"
  hidden
  aria-hidden="true"
  role="dialog"
  aria-modal="true"
  aria-labelledby="bingPhoneChoiceTitle"
>
  <div class="bing-phone-choice__backdrop" data-bing-phone-close tabindex="-1"></div>
  <div class="bing-phone-choice__dialog">
    <button type="button" class="bing-phone-choice__close" data-bing-phone-close aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>

    <div class="bing-phone-choice__panel" data-bing-phone-panel="choice">
      <h2 id="bingPhoneChoiceTitle" class="bing-phone-choice__title">How would you like to reach us?</h2>
      <p class="bing-phone-choice__hours">
        Our team is available <strong>Mon–Fri, 9:00 AM – 7:00 PM (PT)</strong>.
        We’re closed on weekends — leave your number and <strong>we’ll still get back to you</strong>.
      </p>
      <div class="bing-phone-choice__actions">
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--primary" data-bing-phone-action="call">
          Call now
        </button>
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--secondary" data-bing-phone-action="callback">
          Request a callback
        </button>
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--secondary" data-bing-phone-action="text">
          Text us (SMS / WhatsApp)
        </button>
      </div>
    </div>

    <div class="bing-phone-choice__panel" data-bing-phone-panel="text" hidden>
      <h2 class="bing-phone-choice__title">Text us</h2>
      <p class="bing-phone-choice__hours">Choose how you’d like us to message you. We’ll need your phone number next.</p>
      <div class="bing-phone-choice__actions">
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--primary" data-bing-phone-action="sms">
          SMS
        </button>
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--secondary" data-bing-phone-action="whatsapp">
          WhatsApp
        </button>
        <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--ghost" data-bing-phone-action="back">
          Back
        </button>
      </div>
    </div>

    <div class="bing-phone-choice__panel" data-bing-phone-panel="contact" hidden>
      <h2 class="bing-phone-choice__title" data-bing-phone-contact-title>Leave your phone number</h2>
      <p class="bing-phone-choice__hours" data-bing-phone-contact-copy>
        Hours: <strong>Mon–Fri, 9:00 AM – 7:00 PM (PT)</strong>.
        Closed weekends — we’ll still get back to you.
      </p>
      <form class="bing-phone-choice__form" data-bing-phone-contact-form novalidate>
        <input type="hidden" name="channel" value="callback" data-bing-phone-channel>
        <label class="bing-phone-choice__label" for="bingPhoneContactInput">Your phone number</label>
        <input
          id="bingPhoneContactInput"
          class="bing-phone-choice__input"
          type="tel"
          name="phone"
          autocomplete="tel"
          inputmode="tel"
          required
          placeholder="(650) 555-1212"
        >
        <p class="bing-phone-choice__error" data-bing-phone-error hidden></p>
        <p class="bing-phone-choice__success" data-bing-phone-success hidden>
          Thanks — we’ll be in touch soon.
        </p>
        <div class="bing-phone-choice__actions">
          <button type="submit" class="bing-phone-choice__btn bing-phone-choice__btn--primary" data-bing-phone-submit>
            Send request
          </button>
          <button type="button" class="bing-phone-choice__btn bing-phone-choice__btn--ghost" data-bing-phone-action="back-contact">
            Back
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endonce
