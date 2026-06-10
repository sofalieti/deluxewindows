    @if(count($faqs) > 0)
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
                <div class="accordion-wrapper">
                  <div class="accordion-item-wrapper {{ $index === 0 ? 'first' : '' }}{{ $index === count($faqs) - 1 ? ' last' : '' }}">
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
    @endif
