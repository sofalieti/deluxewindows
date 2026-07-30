@php
  $isDoorGuarantee = ($guaranteeContext ?? '') === 'doors';
@endphp
      <div class="f-section-large-3 guarantee-section">
        <div class="f-container-regular-3 guarantee-container">
          <div class="f-margin-bottom-64">
            <div class="w-layout-grid f-header-grid-asymmetrical">
              <div id="w-node-_62415541-8aa0-a03d-fd67-04e3ce71fbe3-ce71fbdf" class="f-max-width-large">
                <img
                  src="/webflow-assets/images/69986ae97432d22237832ac2_guarantee-icon.svg"
                  loading="lazy"
                  width="100"
                  alt=""
                />
                <h3 class="f-h3-heading-2">Our Guarantee</h3>
              </div>
            </div>
          </div>
          <div class="w-layout-grid f-grid-three-column-2">
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $isDoorGuarantee ? 'Entry & Fiberglass Doors' : 'Vinyl windows' }}<br />‍</h5>
              </div>
              <p class="f-paragraph-large-2">
                @if($isDoorGuarantee)
                  Manufacturer-backed coverage for the selected door, glass, and components.
                @else
                  <strong><br />Full lifetime</strong> transferable warranty on parts and labor
                @endif
              </p>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $isDoorGuarantee ? 'Patio & Multi-Panel Doors' : 'Aluminum Wood clad, fiberglass, fiberglass clad' }}</h5>
              </div>
              <p class="f-paragraph-large-2">
                @if($isDoorGuarantee)
                  Coverage options for glass, frames, hardware, and operating components.
                @else
                  <br />Offer <strong>20 year</strong> on glass.
                @endif
              </p>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $isDoorGuarantee ? 'Installation & Service' : 'All Other Parts' }}<br />‍</h5>
              </div>
              <p class="f-paragraph-large-2">
                @if($isDoorGuarantee)
                  Clear installation scope and a final operation check before completion.
                @else
                  <strong><br />10&nbsp;Years</strong> Warranty
                @endif
              </p>
            </div>
          </div>
          <p class="f-paragraph-large-2 dsaf">
            {{ $isDoorGuarantee
              ? 'We explain the manufacturer warranty included with your selected door before ordering.'
              : "Manufacturer's warranty on glass and frame - lifetime**" }}
          </p>
        </div>
      </div>