@php
  /** @var string $scope */
  /** @var array<int, array{id: string, slug: string, name: string}> $items */
  /** @var array<string, array{base: string, final: string}> $values */
  /** @var string $help */
  $tabId = 'promo-tab-'.md5($scope);
@endphp

<div class="promo-pricing-tab" data-tab-id="{{ $tabId }}">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="text-muted">{{ $help }}</div>
    <button type="button" class="btn btn-sm btn-outline-primary" data-action="expand-all">Развернуть все</button>
  </div>

  @if($items === [])
    <div class="alert alert-secondary mb-0">No records available for this tab.</div>
  @else
    @foreach($items as $item)
      @php
        $id = $item['id'];
        $slug = $item['slug'];
        $name = $item['name'];
        $base = (string) ($values[$id]['base'] ?? '');
        $final = (string) ($values[$id]['final'] ?? '');
        $hasValue = trim($base) !== '' || trim($final) !== '';
      @endphp

      <div class="card mb-2 promo-pricing-item {{ $hasValue ? '' : 'd-none' }}" data-role="pricing-item" data-empty="{{ $hasValue ? '0' : '1' }}">
        <div class="card-header py-2">
          <button type="button" class="btn btn-link p-0 text-left w-100 text-decoration-none text-dark" data-action="toggle-item">
            <strong>{{ $name }}</strong>
            <span class="text-muted">({{ $slug }})</span>
            <span class="badge badge-light float-right">{{ $hasValue ? 'filled' : 'empty' }}</span>
          </button>
        </div>
        <div class="card-body pt-3 pb-2" data-role="item-body" style="display:none;">
          <input type="hidden" name="meta[{{ $scope }}][{{ $id }}][name]" value="{{ $name }} ({{ $slug }}) [{{ $id }}]" />

          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Base</label>
              <input
                type="text"
                class="form-control"
                name="promotions[{{ $scope }}][{{ $id }}][base]"
                value="{{ $base }}"
                placeholder="e.g. 1199"
              />
            </div>
            <div class="col-md-6">
              <label class="form-label">Final</label>
              <input
                type="text"
                class="form-control"
                name="promotions[{{ $scope }}][{{ $id }}][final]"
                value="{{ $final }}"
                placeholder="e.g. 799"
              />
            </div>
          </div>
        </div>
      </div>
    @endforeach
  @endif
</div>

<script>
  (function () {
    if (window.__promoPricingTabInit !== true) {
      window.__promoPricingTabInit = true;

      document.addEventListener('click', function (event) {
        const toggleBtn = event.target.closest('[data-action="toggle-item"]');
        if (toggleBtn) {
          event.preventDefault();
          const item = toggleBtn.closest('[data-role="pricing-item"]');
          const body = item ? item.querySelector('[data-role="item-body"]') : null;
          if (body) {
            body.style.display = body.style.display === 'none' ? '' : 'none';
          }
          return;
        }

        const expandAllBtn = event.target.closest('[data-action="expand-all"]');
        if (!expandAllBtn) {
          return;
        }

        event.preventDefault();
        const tab = expandAllBtn.closest('.promo-pricing-tab');
        if (!tab) {
          return;
        }

        const items = tab.querySelectorAll('[data-role="pricing-item"]');
        items.forEach(function (item) {
          item.classList.remove('d-none');
          const body = item.querySelector('[data-role="item-body"]');
          if (body) {
            body.style.display = '';
          }
        });
      });
    }
  })();
</script>
