@php
  /** @var array<int, array{href: string, image?: string, alt?: string}> $items */
  $items = is_array($items ?? null) ? $items : [];
  $title = isset($title) ? trim((string) $title) : '';
  $wrapperClass = trim((string) ($wrapperClass ?? ''));
@endphp

@once
  <style>
    .brand-strip {
      width: 100%;
      overflow: hidden;
    }

    .brand-strip__title {
      margin-bottom: 18px;
    }

    .brand-strip__list {
      display: flex;
      flex-wrap: nowrap;
      align-items: center;
      gap: 22px;
      width: max-content;
      min-width: 300%;
      animation: brand-strip-scroll 22s linear infinite;
    }

    .brand-strip__item {
      flex: 0 0 auto;
      min-width: 0;
    }

    .brand-strip__link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 48px;
      padding: 6px 8px;
      border: none;
      border-radius: 0;
      background: transparent;
      text-decoration: none;
    }

    .brand-strip__image {
      max-width: 100%;
      max-height: 28px;
      width: auto;
      height: auto;
      object-fit: contain;
      display: block;
      mix-blend-mode: darken;
    }

    .brand-strip__item--dup {
      display: block;
    }

    @media (max-width: 767px) {
      .brand-strip__list {
        gap: 18px;
        animation: brand-strip-scroll 16s linear infinite;
      }

      .brand-strip__link {
        justify-content: center;
        min-height: 42px;
        padding: 0;
      }

      .brand-strip__image {
        max-height: 14px;
      }
    }

    @keyframes brand-strip-scroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-33.333333%);
      }
    }
  </style>
@endonce

<div class="brand-strip {{ $wrapperClass }}">
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
            <img src="{{ $image }}" alt="{{ $alt }}" loading="lazy" class="brand-strip__image" />
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
            <img src="{{ $image }}" alt="" loading="lazy" class="brand-strip__image" />
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
            <img src="{{ $image }}" alt="" loading="lazy" class="brand-strip__image" />
          @else
            <span class="text-muted">{{ $alt !== '' ? $alt : 'Brand' }}</span>
          @endif
        </a>
      </div>
    @endforeach
  </div>
</div>
