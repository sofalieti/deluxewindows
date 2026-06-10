    @if(count($faqs) > 0)
    @php
      // Webflow ix2 interaction targets for service-area template (data-wf-page 69ce7898d019bc268b4bb9e4)
      $faqAccordionWids = [
        '7c2f1aab-0a7c-7750-8c1c-d24e5259cc04',
        '7c2f1aab-0a7c-7750-8c1c-d24e5259cc14',
        '7c2f1aab-0a7c-7750-8c1c-d24e5259cc22',
        '7c2f1aab-0a7c-7750-8c1c-d24e5259cc32',
        '7c2f1aab-0a7c-7750-8c1c-d24e5259cc02',
      ];
    @endphp
    <section class="section">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="inner-container _670px center">
          <div class="center-content">
            <div class="badge secondary mid">
              <div class="badge-icon-left secondary">
                <img src="/webflow-assets/images/6841ddf8ace3d9d9facb1854_message-icon-property-x-webflow-template.svg" loading="eager" alt="Message Icon - Property X Webflow Template" />
              </div>
              <div class="display-1">FAQs</div>
            </div>
            <div class="mg-top-small">
              <h2>Window Replacement FAQs</h2>
            </div>
          </div>
        </div>
        <div class="mg-top-medium mg-top-32px---mbl">
          <div class="inner-container _843px center">
            <div class="card accordion-card-v1">
              <div class="w-layout-grid grid-1-column accordion-v6">
                @foreach($faqs as $index => $faq)
                @php
                  $wId = $faqAccordionWids[$index] ?? null;
                @endphp
                <div
                  @if($wId)
                  id="w-node-_{{ str_replace('-', '_', $wId) }}-8b4bb9e4"
                  data-w-id="{{ $wId }}"
                  @endif
                  class="accordion-wrapper{{ $wId ? '' : ' js-faq-accordion' }}"
                >
                  <div class="accordion-item-wrapper {{ $index === 0 ? 'first' : '' }}{{ $index === count($faqs) - 1 ? ' last' : '' }}">
                    <div class="accordion-top">
                      <div class="text-titles">
                        <h3 class="faqs-title">{{ $faq['question'] }}</h3>
                      </div>
                      <div class="accordion-icon-wrapper" style="background-color: rgba(0, 0, 0, 0)">
                        <div
                          class="accordion-icon-line vertical"
                          style="background-color: rgb(20, 22, 28); transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(90deg) skew(0deg, 0deg); transform-style: preserve-3d;"
                        ></div>
                        <div class="accordion-icon-line" style="background-color: rgb(20, 22, 28)"></div>
                      </div>
                    </div>
                    <div class="accordion-bottom v1" style="height: 0px">
                      <div class="w-richtext">
                        {!! $faq['answer'] !!}
                      </div>
                    </div>
                  </div>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
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
