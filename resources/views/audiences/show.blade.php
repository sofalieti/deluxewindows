@extends('layouts.classic')

@section('head')
  <style>html:not(.dw-bing) body{visibility:hidden!important}</style>
  <script>
    if (!document.documentElement.classList.contains('dw-bing')) {
      location.replace('/');
    }
  </script>
@endsection

@section('bodyClass', 'audience-page')

@section('content')
  <article class="who-page">
    <section class="section who-page__hero">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-2-columns image-left-grid-v1">
          <div class="inner-container _613px _100-mbl">
            <div class="image-wrapper">
              <x-img
                :src="$audience['image']"
                preset="content"
                :alt="$audience['image_alt']"
                loading="eager"
                class="image cover-image grid-image-left who-page__image"
              />
            </div>
          </div>
          <div class="mission-grid---right-content-wrapper">
            <div class="badge-wrapper">
              <div class="badge secondary mid">
                <div class="display-1">Who are you</div>
              </div>
            </div>
            <div class="mg-top-small">
              <h1 class="heading-2">{{ $audience['h1'] }}</h1>
            </div>
            <div class="mg-top-small">
              <p class="paragraph-10">{{ $audience['lede'] }}</p>
            </div>
            <div class="mg-top-extra-small">
              <p class="paragraph-17">{{ $audience['card'] }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section top-none" aria-label="How we work with you">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="heading-6">How we work with you</h2>
        <div class="who-page__points">
          @foreach($audience['points'] as $point)
            <div class="value-wrapper">
              <h3 class="display-5 mid">{{ $point['title'] }}</h3>
              <div class="mg-top-extra-small">
                <p class="paragraph-5">{{ $point['text'] }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <section class="section top-none" aria-label="What happens next">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="heading-6">What happens next</h2>
        <ol class="who-page__steps">
          @foreach($audience['steps'] as $step)
            <li>{{ $step }}</li>
          @endforeach
        </ol>
      </div>
    </section>

    <section class="section top-none" id="contact" aria-label="Request a conversation">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-2-columns contact-grid-v2">
          <div class="inner-container _440px _100-tablet">
            <h2 class="heading-6">Tell us about the project</h2>
            <div class="mg-top-small">
              <p class="paragraph-8">A short note is enough. We reply with the next step, not a generic brochure.</p>
            </div>
          </div>
          <div class="inner-container _659px width-100 _100-tablet">
            @include('partials.contact-form', ['formId' => $audience['form_id']])
          </div>
        </div>
      </div>
    </section>

    <nav class="section top-none who-page__others" aria-label="Other paths">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="heading-6">Other paths</h2>
        <div class="who-page__other-row">
          @foreach($paths as $path)
            @continue($path['slug'] === $audience['slug'])
            <a class="link mid" href="{{ \App\Support\AudiencePaths::url($path['slug']) }}">{{ $path['title'] }}</a>
          @endforeach
        </div>
      </div>
    </nav>
  </article>
@endsection
