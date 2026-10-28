@php
  $msHeroTitle = $msHeroTitle ?? 'Window and door replacement in the Bay Area with professional installation.';
  $msHeroPromise = $msHeroPromise ?? 'Free in-home measure and a written estimate.';
  $msHeroHeading = ($msHeroHeading ?? 'h1') === 'h2' ? 'h2' : 'h1';
  $msHeroTitleHtml = $msHeroTitleHtml ?? null;
@endphp
<section class="bing-only ms-home-hero" aria-labelledby="ms-home-hero-title">
  <{{ $msHeroHeading }} id="ms-home-hero-title" class="ms-home-hero__title">
    @if($msHeroTitleHtml)
      {!! $msHeroTitleHtml !!}
    @else
      {{ $msHeroTitle }}
    @endif
  </{{ $msHeroHeading }}>
  @if(trim(strip_tags((string) $msHeroPromise)) !== '')
    <p class="ms-home-hero__promise">{!! $msHeroPromise !!}</p>
  @endif

  <div class="ms-home-hero__actions">
    <button type="button" class="ms-home-hero__btn" data-open-estimate-modal>
      Quick Estimate
    </button>
  </div>
  @include('partials.service-area-phones', [
    'localPhone' => $localPhone ?? null,
    'localLabel' => $localLabel ?? null,
  ])
  <div class="ms-home-hero__proof">
    <div class="ms-home-hero__badge" data-ms-review-slot></div>
  </div>
  @if(!empty($msHeroBrands))
    @include('partials.hero-brands', ['heroBrandsPlacement' => 'desktop', 'heroBrandsBing' => true])
  @endif
</section>
