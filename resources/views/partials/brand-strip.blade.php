@php
  /** @var array<int, array{href: string, image?: string, alt?: string}> $items */
  $items = is_array($items ?? null) ? $items : [];
  $title = isset($title) ? trim((string) $title) : '';
  $wrapperClass = trim((string) ($wrapperClass ?? ''));
  $isStatic = ($variant ?? '') === 'static';
@endphp

<div class="brand-strip {{ $isStatic ? 'brand-strip--static' : '' }} {{ $wrapperClass }}">
  @if($title !== '')
    <div class="title-left---content-right brand-strip__title">
      <h2 class="heading-23">{{ $title }}</h2>
    </div>
  @endif

  <div class="brand-strip__list">
    @foreach($items as $item)
      @php
        $href = trim((string) ($item['href'] ?? ''));
        $image = trim((string) ($item['image'] ?? ''));
        $alt = trim((string) ($item['alt'] ?? ''));
      @endphp
      <div class="brand-strip__item">
        <a href="{{ $href !== '' ? $href : '#' }}" class="brand-strip__link w-inline-block">
          @if($image !== '')
            <img src="{{ webflow_image_url($image) }}" alt="{{ $alt }}" loading="lazy" class="brand-strip__image" />
          @else
            <span class="text-muted">{{ $alt !== '' ? $alt : 'Brand' }}</span>
          @endif
        </a>
      </div>
    @endforeach

    @unless($isStatic)
    @foreach($items as $item)
      @php
        $href = trim((string) ($item['href'] ?? ''));
        $image = trim((string) ($item['image'] ?? ''));
        $alt = trim((string) ($item['alt'] ?? ''));
      @endphp
      <div class="brand-strip__item brand-strip__item--dup" aria-hidden="true">
        <a href="{{ $href !== '' ? $href : '#' }}" class="brand-strip__link w-inline-block" tabindex="-1">
          @if($image !== '')
            <img src="{{ webflow_image_url($image) }}" alt="" loading="lazy" class="brand-strip__image" />
          @else
            <span class="text-muted">{{ $alt !== '' ? $alt : 'Brand' }}</span>
          @endif
        </a>
      </div>
    @endforeach

    @foreach($items as $item)
      @php
        $href = trim((string) ($item['href'] ?? ''));
        $image = trim((string) ($item['image'] ?? ''));
        $alt = trim((string) ($item['alt'] ?? ''));
      @endphp
      <div class="brand-strip__item brand-strip__item--dup" aria-hidden="true">
        <a href="{{ $href !== '' ? $href : '#' }}" class="brand-strip__link w-inline-block" tabindex="-1">
          @if($image !== '')
            <img src="{{ webflow_image_url($image) }}" alt="" loading="lazy" class="brand-strip__image" />
          @else
            <span class="text-muted">{{ $alt !== '' ? $alt : 'Brand' }}</span>
          @endif
        </a>
      </div>
    @endforeach
    @endunless
  </div>
</div>
