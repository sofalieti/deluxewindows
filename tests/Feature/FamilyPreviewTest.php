<?php

test('material and brand heroes have a microsoft family redraw', function () {
    $groups = [
        'window-type-hero' => [
            'vinyl-windows',
            'wood-windows',
            'wood-clad-windows',
            'steel-windows',
            'aluminum-windows',
            'aluminum-clad-windows',
            'fiberglass-windows',
        ],
        'door-type-hero' => [
            'vinyl-doors',
            'wood-doors',
            'wood-clad-doors',
            'steel-doors',
            'aluminum-doors',
            'fiberglass-doors',
        ],
        'brand-hero' => [
            'marvin',
            'milgard',
            'ply-gem',
            'western-window-systems',
            'anlin',
            'simonton',
            'alside',
            'andersen',
            'jeld-wen',
            'italwindows',
            'all-weather-architectural-aluminum',
        ],
    ];

    foreach ($groups as $directory => $slugs) {
        foreach ($slugs as $slug) {
            $family = public_path("webflow-assets/images/{$directory}/{$slug}-family.webp");
            expect(is_file($family))->toBeTrue();

            $url = family_hero_variant("/webflow-assets/images/{$directory}/{$slug}.avif?v=1");
            expect($url)->toContain("/webflow-assets/images/{$directory}/{$slug}-family.");

            $legacy = family_hero_variant("/webflow-assets/images/{$directory}/{$slug}-hero-bg-v1.avif");
            expect($legacy)->toContain("/webflow-assets/images/{$directory}/{$slug}-family.");
        }
    }

    $doorStyle = hero_background_style(
        '/webflow-assets/images/brand-hero/marvin.avif',
        '/webflow-assets/images/door-brand-hero/marvin.avif',
    );
    expect($doorStyle)->toContain('door-brand-hero/marvin-family.avif')
        ->and($doorStyle)->toContain('brand-hero/marvin');

    expect(family_hero_variant('/webflow-assets/images/logo.svg'))->toBeNull()
        ->and(family_hero_variant(null))->toBeNull();

    $style = hero_background_style('/webflow-assets/images/window-type-hero/vinyl-windows.avif');
    expect($style)->not->toContain('background-image:')
        ->and($style)->toContain('--dw-hero:url(')
        ->and($style)->toContain('--dw-family-hero:url(')
        ->and($style)->toContain('vinyl-windows-family.avif');

    $css = file_get_contents(public_path('webflow-overrides/site-custom.css'));
    expect($css)->toContain('html.dw-bing .div-block-61[style*="--dw-family-hero"]')
        ->and($css)->not->toContain('html.dw-family');
});

test('the regular homepage does not switch on the family hero', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('-family.avif')
        ->and($html)->not->toContain('-family.webp');
});
