<section class="who-paths bing-only" aria-labelledby="who-paths-title">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="who-paths__head">
      <div>
        <h2 id="who-paths-title" class="who-paths__title">Who Are You?</h2>
        <p class="who-paths__lede">The right windows and the right process for every kind of project.</p>
      </div>
      <a class="who-paths__jump" href="#who-paths">Find your path</a>
    </div>
    <div class="who-paths__grid" id="who-paths">
      @foreach(\App\Support\AudiencePaths::all() as $audience)
        <a class="who-card" href="{{ \App\Support\AudiencePaths::url($audience['slug']) }}">
          <div class="who-card__art">
            @include('partials.audience-art', ['art' => $audience['slug']])
          </div>
          <div class="who-card__body">
            <h3 class="who-card__title">{{ $audience['title'] }}</h3>
            <p class="who-card__text">{{ $audience['card'] }}</p>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
