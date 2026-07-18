@if($pageMetadata->faq !== [])
<section class="section top-none page-metadata-faq" aria-labelledby="page-metadata-faq-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-grid grid-2-columns faqs-grid-v3">
      <div class="sticky-top static---mbl">
        <div class="inner-container _450px---mbl">
          <div class="inner-container _275px---tablet _100-mbl">
            <div class="inner-container _340px _100-mbl">
              <div class="mg-top-small">
                <h2 id="page-metadata-faq-heading" class="heading-44">Do You Have Any Question?</h2>
              </div>
              <div class="div-block-49">
                <p class="paragraph-2">
                  Call us at
                  <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a>
                  to ask your questions.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="inner-container _763px width-100">
        <div class="card accordion-card v2">
          <div class="w-layout-grid grid-1-column accordion-v6">
            @foreach($pageMetadata->faq as $item)
            <details
              class="accordion-item-wrapper v2{{ $loop->first ? ' first' : '' }}{{ $loop->last ? ' last' : '' }}"
            >
              <summary class="accordion-top">
                <span class="text-titles">
                  <span class="faqs-title">{{ $item['question'] }}</span>
                </span>
                <span class="accordion-icon-wrapper" aria-hidden="true">
                  <span class="accordion-icon-line vertical"></span>
                  <span class="accordion-icon-line"></span>
                </span>
              </summary>
              <div class="accordion-bottom v1">
                <p class="accordion-paragraph">{{ $item['answer'] }}</p>
              </div>
            </details>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="new-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="text-block-44">* Price applies to minimum window installation size of 24&quot;x24&quot;.</div>
  </div>
</section>
@endif
