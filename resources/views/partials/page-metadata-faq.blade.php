@if($pageMetadata->faq !== [])
<section class="page-metadata-faq" aria-labelledby="page-metadata-faq-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="page-metadata-faq__header">
      <span class="page-metadata-faq__eyebrow">FAQs</span>
      <h2 id="page-metadata-faq-heading">Frequently Asked Questions</h2>
    </div>

    <div class="page-metadata-faq__items">
      @foreach($pageMetadata->faq as $item)
      <details class="page-metadata-faq__item">
        <summary>{{ $item['question'] }}</summary>
        <div class="page-metadata-faq__answer">
          <p>{{ $item['answer'] }}</p>
        </div>
      </details>
      @endforeach
    </div>
  </div>
</section>
@endif
