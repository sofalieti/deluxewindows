@php
  $doorFaqItems = collect($faqItems ?? [])
    ->map(function ($item) {
      if (! is_array($item)) {
        return null;
      }
      $q = trim((string) ($item['question'] ?? ''));
      $a = trim((string) ($item['answer'] ?? ''));
      return $q !== '' && $a !== '' ? ['question' => $q, 'answer' => $a] : null;
    })
    ->filter()
    ->values();

  // Webflow ix2 interaction targets available on the brand template (data-wf-page 6841ddf8ace3d9d9facb1583).
  // The first item is open by default (no interaction id); items 2..4 reuse these ids.
  $doorFaqWids = [
    '5e6fa5f4-992b-f428-8721-43b1fd267cb8',
    '5e6fa5f4-992b-f428-8721-43b1fd267cc6',
    '5e6fa5f4-992b-f428-8721-43b1fd267cd6',
  ];
  $doorFaqCount = $doorFaqItems->count();
@endphp

@if($doorFaqCount > 0)
      <section class="section top-none{{ !empty($sectionExtraClass) ? ' ' . $sectionExtraClass : '' }}">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns faqs-grid-v3">
            <div class="sticky-top static---mbl">
              <div class="inner-container _450px---mbl">
                <div class="inner-container _275px---tablet _100-mbl">
                  <div class="inner-container _340px _100-mbl">
                    <div class="mg-top-small"><h2 class="heading-44">{{ $faqHeading ?? 'Do You Have Any Question?' }}</h2></div>
                    <div class="div-block-49">
                      <p class="paragraph-2">
                        Call us at <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a> to <br />ask your questions.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div id="w-node-_5e6fa5f4-992b-f428-8721-43b1fd267ca5-fd267c94" class="inner-container _763px width-100">
              <div class="card accordion-card v2">
                <div class="w-layout-grid grid-1-column accordion-v6">
                  @foreach($doorFaqItems as $index => $faq)
                    @php
                      $isFirst = $index === 0;
                      $isLast  = $index === $doorFaqCount - 1;
                      // First item: open, no wrapper. Items 2..4 use webflow ix2 ids. Extras use JS fallback.
                      $wId = ! $isFirst ? ($doorFaqWids[$index - 1] ?? null) : null;
                      $useFallback = ! $isFirst && $wId === null;
                    @endphp

                    @if($isFirst)
                    <div class="accordion-item-wrapper v2 first">
                      <div class="accordion-top">
                        <div class="text-titles">
                          <h3 class="faqs-title">{{ $faq['question'] }}</h3>
                        </div>
                        <div class="accordion-icon-wrapper">
                          <div class="accordion-icon-line vertical"></div>
                          <div class="accordion-icon-line"></div>
                        </div>
                      </div>
                      <div class="accordion-bottom v1">
                        <p class="accordion-paragraph">{!! $faq['answer'] !!}</p>
                      </div>
                    </div>
                    @else
                    <div
                      @if($wId)
                      id="w-node-_{{ str_replace('-', '_', $wId) }}-fd267c94"
                      data-w-id="{{ $wId }}"
                      @endif
                      class="accordion-wrapper{{ $useFallback ? ' js-faq-accordion' : '' }}"
                    >
                      <div class="accordion-item-wrapper v2{{ $isLast ? ' last' : '' }}">
                        <div class="accordion-top">
                          <div class="text-titles"><h3 class="faqs-title">{{ $faq['question'] }}</h3></div>
                          <div class="accordion-icon-wrapper" style="background-color: rgba(0, 0, 0, 0)">
                            <div
                              class="accordion-icon-line vertical"
                              style="background-color: rgb(20, 22, 28); transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(90deg) skew(0deg, 0deg); transform-style: preserve-3d;"
                            ></div>
                            <div class="accordion-icon-line" style="background-color: rgb(20, 22, 28)"></div>
                          </div>
                        </div>
                        <div class="accordion-bottom v1" style="height: 0px">
                          @if($useFallback)
                          <p class="accordion-paragraph">{!! $faq['answer'] !!}</p>
                          @else
                          <p
                            class="accordion-paragraph"
                            style="opacity: 0; transform: translate3d(0px, 8px, 0px) scale3d(0.98, 0.98, 1) rotateX(0deg) rotateY(0deg) rotateZ(0deg) skew(0deg, 0deg); transform-style: preserve-3d;"
                          >{!! $faq['answer'] !!}</p>
                          @endif
                        </div>
                      </div>
                    </div>
                    @endif
                  @endforeach
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      @if($doorFaqItems->count() > 4)
      <script>
      (function () {
        document.querySelectorAll('.js-faq-accordion').forEach(function (wrapper) {
          var top = wrapper.querySelector('.accordion-top');
          var bottom = wrapper.querySelector('.accordion-bottom');
          var vertical = wrapper.querySelector('.accordion-icon-line.vertical');
          if (!top || !bottom) return;

          top.style.cursor = 'pointer';
          top.addEventListener('click', function () {
            var open = wrapper.classList.toggle('is-open');
            bottom.style.height = open ? bottom.scrollHeight + 'px' : '0px';
            if (vertical) {
              vertical.style.transform = open
                ? 'translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(0deg) skew(0deg, 0deg)'
                : 'translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(90deg) skew(0deg, 0deg)';
            }
          });
        });
      })();
      </script>
      @endif
@endif
