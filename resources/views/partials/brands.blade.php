@php
  $brandStripItems = [
    ['href' => '/brands/marvin', 'image' => '/webflow-assets/images/6915aaca08003de3e1e57018_marvin-logo-black.svg', 'alt' => 'Marvin'],
    ['href' => '/brands/milgard', 'image' => '/webflow-assets/images/6915aaea85f921adbca8a4e7_milgard.svg', 'alt' => 'Milgard'],
    ['href' => '/brands/anlin', 'image' => '/webflow-assets/images/6915c80af96503367881f15f_anlin2.svg', 'alt' => 'Anlin'],
    ['href' => '/brands/jeld-wen', 'image' => '/webflow-assets/images/6915aa60264a3c99f69524c6_jv.svg', 'alt' => 'Jeld Wen'],
    ['href' => '/brands/andersen', 'image' => '/webflow-assets/images/6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg', 'alt' => 'Andersen'],
    ['href' => '/brands/all-weather-architectural-aluminum', 'image' => '/webflow-assets/images/6915bedcc5e0152198130ace_footer-logo__1__2-removebg-preview.avif', 'alt' => 'All Weather'],
    ['href' => '/brands/western-window-systems', 'image' => '/webflow-assets/images/6915b390bad100b6e6176ea7_westerngroup.svg', 'alt' => 'Western Window Systems'],
    ['href' => '/brands/alside', 'image' => '/webflow-assets/images/6915b29da8bcdcb16ec593b6_alside-logo.svg', 'alt' => 'Alside'],
    ['href' => '/brands/simonton', 'image' => '/webflow-assets/images/6915aa3a24afaaa0a93dd455_Simonton_PrimaryLogo_Inline_RGB_Gradient_0822-1-2048x427.avif', 'alt' => 'Simonton'],
    ['href' => '/brands/italwindows', 'image' => '/webflow-assets/images/6915bd3fcaf3c1f1ff04d9dd_italwindows.svg', 'alt' => 'Italwindows'],
    ['href' => '/brands/ply-gem', 'image' => '/webflow-assets/images/6915aa80238022f9197f6973_pl.svg', 'alt' => 'Ply Gem'],
  ];
@endphp

@include('partials.brand-strip', [
  'items' => $brandStripItems,
  'wrapperClass' => 'div-block-51',
])