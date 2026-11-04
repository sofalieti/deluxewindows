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
    </div>
  </div>
</section>
