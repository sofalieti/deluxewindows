@php
  $art = $art ?? '';
@endphp
@switch($art)
  @case('homeowners')
    <svg viewBox="0 0 640 460" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect width="640" height="460" fill="#d5e6f0"/>
      <rect y="300" width="640" height="160" fill="#c5d6b4"/>
      <path d="M70 250 L250 118 L430 250 Z" fill="#c4613a"/>
      <rect x="118" y="248" width="264" height="150" fill="#f4ead8"/>
      <rect x="292" y="168" width="36" height="84" fill="#efe4d0"/>
      <rect x="286" y="150" width="48" height="22" fill="#b9b3a8"/>
      <path d="M150 318 Q196 262 242 318 Z" fill="#7eafc4"/>
      <path d="M150 318 Q196 286 242 318" fill="none" stroke="#f4ead8" stroke-width="8"/>
      <rect x="292" y="286" width="58" height="72" fill="#8eb8cb"/>
      <rect x="292" y="286" width="58" height="72" fill="none" stroke="#f4ead8" stroke-width="6"/>
      <path d="M80 398 Q140 360 190 398" fill="#6f8f62"/>
      <path d="M360 404 Q430 352 500 404" fill="#7d9a68"/>
      <rect x="40" y="392" width="560" height="8" fill="#b7c8a4"/>
    </svg>
    @break
  @case('hoas')
    <svg viewBox="0 0 640 460" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect width="640" height="460" fill="#d7e7f1"/>
      <rect y="318" width="640" height="142" fill="#d5c4a4"/>
      <path d="M36 210 L168 112 L300 210 Z" fill="#8d96a0"/>
      <path d="M300 210 L432 112 L564 210 Z" fill="#8d96a0"/>
      <rect x="70" y="208" width="196" height="168" fill="#e7d3b4"/>
      <rect x="334" y="208" width="196" height="168" fill="#ecd8b8"/>
      @foreach([92, 154, 216] as $x)
        <rect x="{{ $x }}" y="236" width="34" height="44" fill="#9ec0d0"/>
        <rect x="{{ $x + 264 }}" y="236" width="34" height="44" fill="#9ec0d0"/>
      @endforeach
      @foreach([110, 374] as $x)
        <rect x="{{ $x }}" y="312" width="36" height="64" fill="#6e5344"/>
      @endforeach
      <rect x="48" y="372" width="540" height="10" fill="#cbb892"/>
      <path d="M20 400 Q80 360 130 400" fill="#8ea36f"/>
      <path d="M500 404 Q560 362 620 404" fill="#8ea36f"/>
    </svg>
    @break
  @case('property-managers')
    <svg viewBox="0 0 640 460" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect width="640" height="460" fill="#d5e4ee"/>
      <rect y="340" width="640" height="120" fill="#c9d5b8"/>
      <rect x="150" y="78" width="340" height="292" fill="#8d4d45"/>
      <rect x="168" y="96" width="304" height="256" fill="#7d433c"/>
      @for($row = 0; $row < 4; $row++)
        @for($col = 0; $col < 5; $col++)
          <rect x="{{ 184 + $col * 56 }}" y="{{ 112 + $row * 58 }}" width="36" height="40" fill="#d7e7ef"/>
          <path d="M{{ 202 + $col * 56 }} {{ 112 + $row * 58 }} v40 M{{ 184 + $col * 56 }} {{ 132 + $row * 58 }} h36" stroke="#8d4d45" stroke-width="2"/>
        @endfor
      @endfor
      <rect x="286" y="292" width="68" height="78" fill="#5c4038"/>
      <path d="M70 360 Q150 300 230 370" fill="#6d8a58"/>
      <path d="M420 368 Q510 310 590 372" fill="#6d8a58"/>
    </svg>
    @break
  @case('designers-architects')
    <svg viewBox="0 0 640 460" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect width="640" height="460" fill="#f7f4ee"/>
      <path d="M48 390 H592" stroke="#243056" stroke-width="1.6" fill="none"/>
      <path d="M80 390 V86 H420 V390" stroke="#243056" stroke-width="1.8" fill="none"/>
      <path d="M150 86 V40 H470 V300 H420" stroke="#243056" stroke-width="1.4" fill="none"/>
      <path d="M168 148 H332 V300 H168 Z" stroke="#243056" stroke-width="1.6" fill="#e7f2f6"/>
      <path d="M250 148 V300 M168 214 H332" stroke="#243056" stroke-width="1.2" fill="none"/>
      <path d="M120 300 H300 V390 H120 Z" stroke="#243056" stroke-width="1.4" fill="none"/>
      <path d="M150 318 H270" stroke="#243056" stroke-width="1.2"/>
      <path d="M430 300 C470 250 520 270 548 220" stroke="#243056" stroke-width="1.4" fill="none"/>
      <circle cx="548" cy="206" r="18" stroke="#243056" stroke-width="1.4" fill="none"/>
      <path d="M536 214 C548 196 566 208 560 222" stroke="#2f6b45" stroke-width="1.4" fill="none"/>
    </svg>
    @break
  @case('builders')
    <svg viewBox="0 0 640 460" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <rect width="640" height="460" fill="#f7f4ee"/>
      <path d="M70 360 H560" stroke="#243056" stroke-width="1.6" fill="none"/>
      <path d="M120 360 V150 L300 70 L480 150 V360" stroke="#243056" stroke-width="1.8" fill="none"/>
      <path d="M210 210 H270 V280 H210 Z M330 210 H400 V280 H330 Z" stroke="#243056" stroke-width="1.5" fill="none"/>
      <path d="M270 360 V290 H350 V360" stroke="#243056" stroke-width="1.5" fill="none"/>
      <path d="M300 70 V40 M250 118 H350" stroke="#243056" stroke-width="1.2" fill="none"/>
      <path d="M500 150 V360 M500 190 H560 M500 250 H545 M500 310 H560" stroke="#243056" stroke-width="1.3" fill="none"/>
      <circle cx="96" cy="300" r="16" stroke="#243056" stroke-width="1.4" fill="none"/>
      <path d="M88 308 C100 286 112 300 108 314" stroke="#2f6b45" stroke-width="1.4" fill="none"/>
    </svg>
    @break
@endswitch
