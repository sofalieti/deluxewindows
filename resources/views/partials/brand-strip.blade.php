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
    }

    .brand-strip__title {
      margin-bottom: 18px;
    }

    .brand-strip__list {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 12px;
      width: 100%;
    }

    .brand-strip__item {
      min-width: 0;
    }

    .brand-strip__link {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 84px;
      padding: 10px 12px;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      background: #fff;
      text-decoration: none;
    }

    .brand-strip__image {
      max-width: 100%;
      max-height: 56px;
      width: auto;
      height: auto;
      object-fit: contain;
      display: block;
    }

    @media (max-width: 991px) {
      .brand-strip__list {
        grid-template-columns: repeat(3, minmax(0, 1fr));
      }
    }

    @media (max-width: 767px) {
      .brand-strip__list {
        grid-template-columns: 1fr;
      }

      .brand-strip__link {
        justify-content: flex-start;
        min-height: 72px;
        padding: 10px 14px;
      }

      .brand-strip__image {
        max-height: 44px;
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
  </div>
</div>
