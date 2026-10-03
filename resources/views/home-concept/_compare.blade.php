{{-- Before/after slider. $p = class prefix; labels optional. --}}
@php
  $p = $p ?? 'hc';
  $labelBefore = $labelBefore ?? 'Before';
  $labelAfter = $labelAfter ?? 'After';
@endphp
<div class="hc-compare {{ $p }}-compare" data-hc-compare>
  <div class="hc-compare__stage">
    <img class="hc-compare__after" src="{{ $c['images']['after'] }}" alt="After: new black-frame windows and a fiberglass entry door" loading="lazy" />
    <div class="hc-compare__before" data-hc-before>
      <img src="{{ $c['images']['before'] }}" alt="Before: aged single-pane aluminum windows" loading="lazy" data-hc-before-img />
    </div>
    <div class="hc-compare__line" data-hc-line><span class="hc-compare__knob" aria-hidden="true">&#x2194;</span></div>
    <span class="hc-compare__lab hc-compare__lab--l">{{ $labelBefore }}</span>
    <span class="hc-compare__lab hc-compare__lab--r">{{ $labelAfter }}</span>
  </div>
  <input class="hc-compare__range" type="range" min="0" max="100" value="50" aria-label="Compare before and after" />
</div>
