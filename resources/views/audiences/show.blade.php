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
    <header class="who-page__hero">
      <div class="w-layout-blockcontainer container-default w-container">
        <p class="who-page__kicker">Who are you</p>
        <div class="who-page__hero-grid">
          <div>
            <h1 class="who-page__title">{{ $audience['h1'] }}</h1>
            <p class="who-page__lede">{{ $audience['lede'] }}</p>
            <p class="who-page__card-line">{{ $audience['card'] }}</p>
          </div>
          <div class="who-page__art">
            @include('partials.audience-art', ['art' => $audience['slug']])
          </div>
        </div>
      </div>
    </header>

    <section class="who-page__section" aria-label="How we work with you">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="who-page__h2">How we work with you</h2>
        <div class="who-page__points">
          @foreach($audience['points'] as $point)
            <div class="who-page__point">
              <h3>{{ $point['title'] }}</h3>
              <p>{{ $point['text'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </section>

    <section class="who-page__section who-page__section--steps" aria-label="What happens next">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="who-page__h2">What happens next</h2>
        <ol class="who-page__steps">
          @foreach($audience['steps'] as $step)
            <li>{{ $step }}</li>
          @endforeach
        </ol>
      </div>
    </section>

    <section class="who-page__section" aria-label="Request a conversation">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="who-page__form-grid">
          <div>
            <h2 class="who-page__h2">Tell us about the project</h2>
            <p class="who-page__form-copy">A short note is enough. We reply with the next step, not a generic brochure.</p>
          </div>
          <div class="who-page__form">
            @include('partials.contact-form', ['formId' => $audience['form_id']])
          </div>
        </div>
      </div>
    </section>

    <nav class="who-page__others" aria-label="Other paths">
      <div class="w-layout-blockcontainer container-default w-container">
        <h2 class="who-page__h2">Other paths</h2>
        <div class="who-page__other-row">
          @foreach($paths as $path)
            @continue($path['slug'] === $audience['slug'])
            <a href="{{ \App\Support\AudiencePaths::url($path['slug']) }}">{{ $path['title'] }}</a>
          @endforeach
        </div>
      </div>
    </nav>
  </article>
@endsection
