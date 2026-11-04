<section class="section who-paths bing-only" aria-labelledby="who-paths-title">
  <div class="text-center---mbl">
    <div class="title-left---content-right">
      <div class="width-100-mobile-landscape">
        <h2 id="who-paths-title" class="heading-46">Who Are You?</h2>
      </div>
    </div>
  </div>
  <div class="w-layout-blockcontainer container-default w-container">
    <p class="paragraph-17 who-paths__lede">The right windows and the right process for every kind of project.</p>
    <div class="who-paths__grid" id="who-paths">
      @foreach(\App\Support\AudiencePaths::all() as $audience)
        <a class="who-card" href="{{ \App\Support\AudiencePaths::url($audience['slug']) }}">
          <div class="image-wrapper who-card__art">
            <x-img
              :src="$audience['image']"
              preset="card"
              loading="lazy"
              :alt="$audience['image_alt']"
              class="image cover-image who-card__image"
            />
          </div>
          <div class="who-card__body">
            <h3 class="display-5 mid">{{ $audience['title'] }}</h3>
            <p class="paragraph-49">{{ $audience['card'] }}</p>
          </div>
        </a>
      @endforeach
      <button type="button" class="who-card who-card--own" data-open-who-own aria-haspopup="dialog" aria-controls="whoOwnModal">
        <span class="who-card__art who-card__art--own">
          <span class="who-card__own-kicker">Your words</span>
          <span class="who-card__own-line">Tell us who you are</span>
        </span>
        <span class="who-card__body">
          <span class="display-5 mid">Write your own</span>
          <span class="paragraph-49">None of these quite fit. Describe the project in your own words.</span>
        </span>
      </button>
    </div>
  </div>
</section>

<div class="mobile-estimate-modal who-own-modal bing-only" id="whoOwnModal" aria-hidden="true">
  <div class="mobile-estimate-modal__backdrop" data-close-who-own></div>
  <div class="mobile-estimate-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="whoOwnTitle">
    <button type="button" class="mobile-estimate-modal__close" data-close-who-own aria-label="Close form">×</button>
    <h3 id="whoOwnTitle" class="mobile-estimate-modal__title">Write your own</h3>
    <p class="who-own-modal__copy">A short note is enough. It is saved with the other requests.</p>
    <div class="mobile-estimate-modal__form-wrap w-form">
      <form class="mobile-estimate-modal__form js-laravel-lead-form" method="post" action="{{ route('contact.submit') }}" data-form-id="Who: Write your own">
        @csrf
        <input type="hidden" name="form_id" value="Who: Write your own" />
        <div class="mobile-estimate-modal__fields">
          <div class="mobile-estimate-modal__row">
            <div class="mobile-estimate-modal__field">
              <label class="mobile-estimate-modal__label" for="who-own-name">Full name</label>
              <input id="who-own-name" type="text" name="Name" placeholder="Full name*" required class="w-input" autocomplete="name" maxlength="255" />
            </div>
            <div class="mobile-estimate-modal__field">
              <label class="mobile-estimate-modal__label" for="who-own-email">Email</label>
              <input id="who-own-email" type="email" name="Email" placeholder="Email" class="w-input" autocomplete="email" maxlength="255" />
            </div>
          </div>
          <div class="mobile-estimate-modal__row">
            <div class="mobile-estimate-modal__field">
              <label class="mobile-estimate-modal__label" for="who-own-phone">Phone</label>
              <input id="who-own-phone" type="tel" name="Phone" placeholder="{{ site_phone_display() }}" required class="w-input" autocomplete="tel" inputmode="tel" maxlength="50" />
            </div>
            <div class="mobile-estimate-modal__field">
              <label class="mobile-estimate-modal__label" for="who-own-city">City</label>
              <input id="who-own-city" type="text" name="Subject" placeholder="City" class="w-input" maxlength="100" />
            </div>
          </div>
          <div data-estimate-message>
            <label class="mobile-estimate-modal__label" for="who-own-message">Your situation</label>
            <textarea id="who-own-message" name="Message" maxlength="3000" required placeholder="Who you are and what you need" class="w-input" rows="4"></textarea>
          </div>
        </div>
        <div class="primary-button space-between-v1">
          <input type="submit" data-wait="Please wait..." class="inside-input-button text-light w-button" value="Send request" />
        </div>
      </form>
      <div class="w-form-done" tabindex="-1" role="region" aria-label="Write your own form success">
        <div>Thank you! Your submission has been received!</div>
      </div>
      <div class="w-form-fail" tabindex="-1" role="region" aria-label="Write your own form failure">
        <div>Oops! Something went wrong while submitting the form.</div>
      </div>
    </div>
  </div>
</div>
<script>
  (function () {
    var modal = document.getElementById('whoOwnModal');
    if (!modal) return;

    function setOpen(open) {
      modal.classList.toggle('is-open', open);
      modal.setAttribute('aria-hidden', open ? 'false' : 'true');
      document.body.style.overflow = open ? 'hidden' : '';
      if (open) {
        var field = modal.querySelector('input[name="Name"]');
        if (field) field.focus();
      }
    }

    document.addEventListener('click', function (event) {
      if (event.target.closest('[data-open-who-own]')) {
        event.preventDefault();
        setOpen(true);
        return;
      }
      if (event.target.closest('[data-close-who-own]')) {
        event.preventDefault();
        setOpen(false);
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) setOpen(false);
    });
  })();
</script>
