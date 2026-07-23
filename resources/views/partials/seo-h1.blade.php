@php
    $seoH1 = trim((string) ($pageMetadata?->h1 ?? ''));
    $seoH1Subline = trim((string) ($pageMetadata?->h1Subline ?? ''));
    $fallbackH1 = trim((string) ($fallbackH1 ?? ''));
    $main = $seoH1 !== '' ? $seoH1 : $fallbackH1;
    $h1Class = trim((string) ($h1Class ?? 'display-8 mid'));
@endphp
@if($main !== '')
<h1 class="{{ $h1Class }}">
  <span class="h1-main">{{ $main }}</span>
  @if($seoH1Subline !== '')
    <span class="h1-subline">{{ $seoH1Subline }}</span>
  @endif
</h1>
@endif
