<div class="form-block-2">
  @if(session('contact_success'))
    <div class="contact-form-success">
      We have received your message and will get back to you as soon as possible.
    </div>
  @else
    <form class="form-3 contact-form-fields" action="{{ route('contact.submit') }}" method="POST">
      @csrf
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="full_name" placeholder="Full Name *"
          value="{{ old('full_name') }}" required maxlength="255" />
        @error('full_name')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="email" name="email" placeholder="Email Address *"
          value="{{ old('email') }}" required maxlength="255" />
        @error('email')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="tel" name="phone" placeholder="Phone Number *"
          value="{{ old('phone') }}" required maxlength="50" />
        @error('phone')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="city" placeholder="City"
          value="{{ old('city') }}" maxlength="100" />
      </div>
      <div class="input-wrapper">
        <textarea class="text-area w-input contact-form-textarea" name="message" placeholder="Short description (optional)"
          rows="3" maxlength="2000">{{ old('message') }}</textarea>
      </div>
      <button type="submit" class="primary-button w-button contact-form-submit">
        Send Message
      </button>
    </form>
  @endif
</div>
