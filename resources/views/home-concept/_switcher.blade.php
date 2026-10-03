{{-- Floating concept switcher (preview helper only). --}}
<nav class="hc-switch" aria-label="Design concepts">
  <span class="hc-switch__label">Concept</span>
  @foreach($c['variants'] as $i => $v)
    <a href="/home-concept/{{ $v }}" class="hc-switch__item {{ $v === $c['variant'] ? 'is-current' : '' }}" title="{{ ucfirst($v) }}">{{ chr(65 + $i) }}</a>
  @endforeach
  <a href="/" class="hc-switch__item hc-switch__item--home" title="Current live home">Live</a>
</nav>
