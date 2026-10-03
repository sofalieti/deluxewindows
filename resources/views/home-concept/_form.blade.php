{{--
  Shared lead form. $p = class prefix (e.g. "ed"), $c = content model.
  Field names match /contact-form + lead-form-scripts (Name, Phone, Email, Subject, Message, Form ID).
--}}
@php
  $p = $p ?? 'hc';
  $formId = 'Home Concept '.ucfirst($c['variant']).' Form';
  $submitLabel = $submitLabel ?? 'Request a free estimate';
  $fine = $fine ?? 'A real person calls or texts within one business day. We never sell your info.';
@endphp
<div class="w-form {{ $p }}-formwrap">
  <form id="hc-quote-form" name="hc-quote-form" method="get" class="{{ $p }}-form" data-form-id="{{ $formId }}" aria-label="Request a free estimate">
    <input type="hidden" name="Form ID" value="{{ $formId }}" />
    <label class="{{ $p }}-field"><span>Full name</span><input type="text" name="Name" autocomplete="name" required maxlength="256" placeholder="Jane Doe" /></label>
    <label class="{{ $p }}-field"><span>Phone</span><input type="tel" name="Phone" autocomplete="tel" inputmode="tel" required maxlength="256" placeholder="{{ $c['phoneDisplay'] }}" /></label>
    <label class="{{ $p }}-field"><span>Email <em>optional</em></span><input type="email" name="Email" autocomplete="email" maxlength="256" placeholder="jane@email.com" /></label>
    <label class="{{ $p }}-field"><span>City</span><input type="text" name="Subject" autocomplete="address-level2" maxlength="256" placeholder="Burlingame" /></label>
    <label class="{{ $p }}-field {{ $p }}-field--wide"><span>Project <em>optional</em></span><input type="text" name="Message" maxlength="5000" placeholder="e.g. 9 windows + a sliding door" /></label>
    <button type="submit" class="{{ $p }}-submit" data-wait="Sending…">{{ $submitLabel }}</button>
    <p class="{{ $p }}-fine">{{ $fine }}</p>
  </form>
  <div class="w-form-done hc-state hc-state--ok" tabindex="-1" role="region" aria-label="Form success">Thank you — your request is in. A specialist will reach out within one business day.</div>
  <div class="w-form-fail hc-state hc-state--err" tabindex="-1" role="region" aria-label="Form failure">Something went wrong. Please call <a href="tel:{{ $c['phoneTel'] }}">{{ $c['phoneDisplay'] }}</a>.</div>
</div>
