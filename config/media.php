<?php

return [

    /** Set MEDIA_THUMBNAILS_ENABLED=false in .env to serve originals only. */
    'enabled' => env('MEDIA_THUMBNAILS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Thumbnail presets (max width in CSS pixels; 2x variant generated for srcset)
    |--------------------------------------------------------------------------
    */
    'presets' => [
        // Product gallery slots use 610:343 — crop at generation time so thumbs match main frame.
        'gallery_main'  => ['width' => 1220, 'height' => 686, 'fit' => 'cover'],
        'gallery_thumb' => ['width' => 220,  'height' => 124, 'fit' => 'cover'],
        'card'          => ['width' => 640,  'height' => null],
        'card_sm'       => ['width' => 400,  'height' => null],
        'logo'          => ['width' => 300,  'height' => null],
        'hero_bg'       => ['width' => 1920, 'height' => null],
        'hero_mobile'   => ['width' => 768,  'height' => null],
        'sidebar'       => ['width' => 160,  'height' => null],
        'brand_grid'    => ['width' => 200,  'height' => null],
        'cta'           => ['width' => 800,  'height' => null],
        'inspiration'   => ['width' => 600,  'height' => null],
        'wtype'         => ['width' => 400,  'height' => null],
        'glass'         => ['width' => 200,  'height' => null],
        'option'        => ['width' => 200,  'height' => null],
        'color'         => ['width' => 120,  'height' => null],
        'footer_logo'   => ['width' => 200,  'height' => null],
        'nav_logo'      => ['width' => 180,  'height' => null],
        'icon'          => ['width' => 48,   'height' => null],
    ],

    'quality'   => 82,
    'format'    => 'webp',
    'disk'      => 'public',
    'directory' => 'thumbnails',

    /** Skip resizing when source is already within this many pixels of target. */
    'skip_within_px' => 8,

];
