<div class="form-block-2">
  @if(session('contact_success'))
    <div class="contact-form-success">
      We have received your message and will get back to you as soon as possible.
    </div>
  @else
    <form class="form-3 contact-form-fields" action="{{ route('contact.submit') }}" method="POST" @if(!empty($questions)) data-audience-form @endif>
      @csrf
      @if(!empty($formId))
        <input type="hidden" name="form_id" value="{{ $formId }}" />
      @endif
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="full_name" placeholder="Full Name *"
          value="{{ old('full_name') }}" required maxlength="255" />
        @error('full_name')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="email" name="email" placeholder="Email Address *"
          value="{{ old('email') }}" required maxlength="255" autocomplete="email" />
        @error('email')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="tel" name="phone" placeholder="Phone Number *"
          value="{{ old('phone') }}" required maxlength="50" autocomplete="tel" inputmode="tel" data-clarity-mask="true" />
        @error('phone')<span class="contact-form-error">{{ $message }}</span>@enderror
      </div>
      <div class="input-wrapper">
        <input class="input w-input" type="text" name="city" placeholder="City"
          value="{{ old('city') }}" maxlength="100" data-clarity-mask="true" />
      </div>
      @foreach($questions ?? [] as $index => $question)
        <div class="input-wrapper">
          <label class="who-ask__label" for="audience-q-{{ $index }}">{{ $question['label'] }}</label>
          <select
            id="audience-q-{{ $index }}"
            class="input w-input who-ask__select"
            name="audience_answers[{{ $index }}][answer]"
            data-audience-question="{{ $question['label'] }}"
            required
          >
            <option value="" disabled selected>Select…</option>
            @foreach($question['options'] as $option)
              <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
          </select>
          <input type="hidden" name="audience_answers[{{ $index }}][question]" value="{{ $question['label'] }}" />
        </div>
      @endforeach
      <div class="input-wrapper">
        <textarea class="text-area w-input contact-form-textarea" name="message" placeholder="{{ !empty($questions) ? 'Anything else we should know (optional)' : 'Short description (optional)' }}"
          rows="3" maxlength="2000">{{ old('message') }}</textarea>
      </div>
      <button type="submit" class="primary-button w-button contact-form-submit">
        Send Message
      </button>
    </form>
  @endif
</div>
