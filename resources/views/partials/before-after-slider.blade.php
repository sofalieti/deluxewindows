@php
    $beforeSrc = (string) ($beforeSrc ?? '');
    $afterSrc = (string) ($afterSrc ?? '');
    $beforeAlt = trim((string) ($beforeAlt ?? 'Before: framed openings without windows installed'));
    $afterAlt = trim((string) ($afterAlt ?? 'After: finished home with windows and doors installed'));
    $compareHeading = trim((string) ($compareHeading ?? 'See the Difference Windows Make'));
    $compareText = trim((string) ($compareText ?? ''));
    $beforeLabel = trim((string) ($beforeLabel ?? 'Before'));
    $afterLabel = trim((string) ($afterLabel ?? 'After'));
    $compareId = 'dw-compare-'.substr(md5($beforeSrc.$afterSrc), 0, 8);
@endphp

<section class="section top-none nc-compare-section" aria-labelledby="{{ $compareId }}-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="nc-compare-header">
      <span class="nc-kicker">Before &amp; after</span>
      <h2 id="{{ $compareId }}-heading" class="display-8 mid">{{ $compareHeading }}</h2>
      @if($compareText !== '')
        <p class="nc-compare-intro">{{ $compareText }}</p>
      @endif
    </div>

    <div class="dw-compare" data-dw-compare>
      <div class="dw-compare__viewport">
        <x-img
          :src="$afterSrc"
          preset="hero_bg"
          loading="lazy"
          :alt="$afterAlt"
          class="dw-compare__image dw-compare__image--after"
        />
        <div class="dw-compare__before" data-dw-compare-before>
          <x-img
            :src="$beforeSrc"
            preset="hero_bg"
            loading="lazy"
            :alt="$beforeAlt"
            class="dw-compare__image dw-compare__image--before"
          />
        </div>
        <div class="dw-compare__divider" data-dw-compare-divider aria-hidden="true">
          <span class="dw-compare__handle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
              <path fill="currentColor" d="M10 6 4 12l6 6V6Zm4 0v12l6-6-6-6Z"/>
            </svg>
          </span>
        </div>
        <span class="dw-compare__badge dw-compare__badge--before">{{ $beforeLabel }}</span>
        <span class="dw-compare__badge dw-compare__badge--after">{{ $afterLabel }}</span>
      </div>
      <label class="dw-compare__range-label" for="{{ $compareId }}-range">
        <span class="dw-compare__sr-only">Drag to compare {{ strtolower($beforeLabel) }} and {{ strtolower($afterLabel) }}</span>
        <input
          id="{{ $compareId }}-range"
          class="dw-compare__range"
          type="range"
          min="0"
          max="100"
          value="50"
          aria-valuemin="0"
          aria-valuemax="100"
          aria-valuenow="50"
          aria-label="Compare {{ $beforeLabel }} and {{ $afterLabel }}"
        />
      </label>
    </div>
  </div>
</section>

@once
  @push('scripts')
    <script>
      (function () {
        function syncBeforeImageWidth(root) {
          var viewport = root.querySelector('.dw-compare__viewport');
          var beforeImg = root.querySelector('.dw-compare__image--before');
          if (!viewport || !beforeImg) return;
          beforeImg.style.width = viewport.offsetWidth + 'px';
          beforeImg.style.height = viewport.offsetHeight + 'px';
        }

        function setPosition(root, percent) {
          var before = root.querySelector('[data-dw-compare-before]');
          var divider = root.querySelector('[data-dw-compare-divider]');
          var range = root.querySelector('.dw-compare__range');
          if (!before || !divider || !range) return;

          percent = Math.max(0, Math.min(100, percent));
          before.style.width = percent + '%';
          divider.style.left = percent + '%';
          range.value = String(Math.round(percent));
          range.setAttribute('aria-valuenow', String(Math.round(percent)));
        }

        function positionFromPointer(root, clientX) {
          var viewport = root.querySelector('.dw-compare__viewport');
          if (!viewport) return;
          var rect = viewport.getBoundingClientRect();
          if (rect.width <= 0) return;
          setPosition(root, ((clientX - rect.left) / rect.width) * 100);
        }

        document.querySelectorAll('[data-dw-compare]').forEach(function (root) {
          var range = root.querySelector('.dw-compare__range');
          var viewport = root.querySelector('.dw-compare__viewport');
          var dragging = false;

          if (range) {
            range.addEventListener('input', function () {
              setPosition(root, Number(range.value));
            });
          }

          if (!viewport) return;

          viewport.addEventListener('pointerdown', function (event) {
            if (event.target === range) return;
            dragging = true;
            viewport.setPointerCapture(event.pointerId);
            positionFromPointer(root, event.clientX);
          });

          viewport.addEventListener('pointermove', function (event) {
            if (!dragging) return;
            positionFromPointer(root, event.clientX);
          });

          viewport.addEventListener('pointerup', function (event) {
            dragging = false;
            if (viewport.hasPointerCapture(event.pointerId)) {
              viewport.releasePointerCapture(event.pointerId);
            }
          });

          viewport.addEventListener('pointercancel', function () {
            dragging = false;
          });

          setPosition(root, 50);
          syncBeforeImageWidth(root);

          if (typeof ResizeObserver !== 'undefined') {
            var viewport = root.querySelector('.dw-compare__viewport');
            if (viewport) {
              new ResizeObserver(function () {
                syncBeforeImageWidth(root);
              }).observe(viewport);
            }
          } else {
            window.addEventListener('resize', function () {
              syncBeforeImageWidth(root);
            });
          }
        });
      })();
    </script>
  @endpush
@endonce
