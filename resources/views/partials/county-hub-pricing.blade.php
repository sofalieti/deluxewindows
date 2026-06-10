@php
  $checkIcon = '<svg width="100%" height="100%" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19.8501 7.25012L9.2501 17.8501C9.15621 17.9448 9.02842 17.998 8.8951 17.998C8.76178 17.998 8.63398 17.9448 8.5401 17.8501L3.1501 12.4601C3.05544 12.3662 3.0022 12.2384 3.0022 12.1051C3.0022 11.9718 3.05544 11.844 3.1501 11.7501L3.8501 11.0501C3.94398 10.9555 4.07178 10.9022 4.2051 10.9022C4.33842 10.9022 4.46621 10.9555 4.5601 11.0501L8.8901 15.3801L18.4401 5.83012C18.6379 5.63833 18.9523 5.63833 19.1501 5.83012L19.8501 6.54012C19.9448 6.634 19.998 6.7618 19.998 6.89512C19.998 7.02844 19.9448 7.15623 19.8501 7.25012Z" fill="currentColor"/></svg>';
@endphp
      <section class="section-124">
        <section class="container-default">
          <div class="rl-padding-global">
            <div class="container-default">
              <div class="rl-padding-section-large">
                <div class="rl_pricing18_component">
                  <div class="rl_pricing18_heading-wrapper">
                    <div class="rl-text-style-subheading"><strong>Transparent Pricing</strong></div>
                    <div class="rl_pricing18_spacing-block-1"></div>
                    <h2 class="rl-heading-style-h2">Window Replacement Cost in {{ $pricingTitle ?? $countyName ?? '' }}</h2>
                    <div class="rl_pricing18_spacing-block-2"></div>
                    <p class="rl-text-style-medium">Bay Area labor rates and materials explained. All pricing includes professional installation.</p>
                  </div>
                  <div class="rl_pricing18_spacing-block-3"></div>
                  <div class="rl_pricing18_plans">
                    <div class="rl_pricing18_plan">
                      <div class="rl_pricing18_plan-content">
                        <div class="rl_pricing18_plan-content-top">
                          <div class="rl_pricing18_price-wrapper top-2">
                            <div class="rl-heading-style-h6">Vinyl Windows</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-heading-style-h1"><code class="code-4">$832</code> $499</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-text-style-regular top-3">per window, installed</div>
                          </div>
                          <div class="rl_pricing18_spacing-block-5"></div>
                          <div class="rl_pricing18_feature-list">
                            @foreach(['Most affordable option', 'Full lifetime warranty available', 'Brands: Anlin, Milgard, Simonton', 'Energy Star certified', 'Low maintenance — never paint'] as $feature)
                            <div class="rl_pricing18_feature">
                              <div class="rl_pricing18_icon-wrapper"><div class="rl_pricing18_icon w-embed">{!! $checkIcon !!}</div></div>
                              <div class="rl-text-style-regular">{{ $feature }}</div>
                            </div>
                            @endforeach
                          </div>
                          <div class="rl_pricing18_spacing-block-6"></div>
                        </div>
                      </div>
                    </div>
                    <div class="rl_pricing18_plan">
                      <div class="rl_pricing18_plan-content">
                        <div class="rl_pricing18_plan-content-top">
                          <div class="rl_pricing18_price-wrapper top-2 top-3">
                            <div class="rl-heading-style-h6">Wood / Wood Clad</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-heading-style-h1"><code class="code-5">$915</code> $549</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-text-style-regular top-3">per window, installed</div>
                          </div>
                          <div class="rl_pricing18_spacing-block-5"></div>
                          <div class="rl_pricing18_feature-list">
                            @foreach(['Natural warmth & character', 'Ideal for historic homes', 'Custom finishes & stain', '20-year glass warranty', 'Brands: Andersen, Marvin, Jeld-Wen'] as $feature)
                            <div class="rl_pricing18_feature">
                              <div class="rl_pricing18_icon-wrapper"><div class="rl_pricing18_icon w-embed">{!! $checkIcon !!}</div></div>
                              <div class="rl-text-style-regular">{{ $feature }}</div>
                            </div>
                            @endforeach
                          </div>
                          <div class="rl_pricing18_spacing-block-6"></div>
                        </div>
                      </div>
                    </div>
                    <div class="rl_pricing18_plan">
                      <div class="rl_pricing18_plan-content">
                        <div class="rl_pricing18_plan-content-top">
                          <div class="rl_pricing18_price-wrapper top-2 top-4">
                            <div class="rl-heading-style-h6">Fiberglass / Aluminum</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-heading-style-h1"><code class="code-5">$915</code> $549</div>
                            <div class="rl_pricing18_spacing-block-4"></div>
                            <div class="rl-text-style-regular top-3">per window, installed</div>
                          </div>
                          <div class="rl_pricing18_spacing-block-5"></div>
                          <div class="rl_pricing18_feature-list">
                            @foreach(['Highest strength & durability', 'Best thermal performance', 'Modern slim-frame aesthetics', 'Ideal for coastal climates', 'Brands: Milgard Ultra, Marvin, WWS'] as $feature)
                            <div class="rl_pricing18_feature">
                              <div class="rl_pricing18_icon-wrapper"><div class="rl_pricing18_icon w-embed">{!! $checkIcon !!}</div></div>
                              <div class="rl-text-style-regular">{{ $feature }}</div>
                            </div>
                            @endforeach
                          </div>
                          <div class="rl_pricing18_spacing-block-6"></div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
      </section>
