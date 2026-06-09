<div class="form-block-2">
  @if(session('contact_success'))
    <div style="padding:28px 24px; background:#edf7f1; border:1px solid #a8d9be; border-radius:14px; text-align:center; color:#1a5c35; font-weight:600; font-size:1rem;">
      We have received your message and will get back to you as soon as possible.
    </div>
  @else
    <form class="form-3" action="{{ route('contact.submit') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
      @csrf
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="full_name" placeholder="Full Name *"
          value="{{ old('full_name') }}" required maxlength="255" />
        @error('full_name')<span style="color:#dc3545;font-size:0.78rem;margin-top:2px;display:block;">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="email" name="email" placeholder="Email Address *"
          value="{{ old('email') }}" required maxlength="255" />
        @error('email')<span style="color:#dc3545;font-size:0.78rem;margin-top:2px;display:block;">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="tel" name="phone" placeholder="Phone Number *"
          value="{{ old('phone') }}" required maxlength="50" />
        @error('phone')<span style="color:#dc3545;font-size:0.78rem;margin-top:2px;display:block;">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="city" placeholder="City"
          value="{{ old('city') }}" maxlength="100" />
      </div>
      <div class="input-wrapper">
        <textarea class="text-area w-input" name="message" placeholder="Short description (optional)"
          rows="3" maxlength="2000" style="resize:vertical;">{{ old('message') }}</textarea>
      </div>
      <button type="submit" class="primary-button w-button" style="width:100%;margin-top:4px;">
        Send Message
      </button>
    </form>
  @endif
</div>
