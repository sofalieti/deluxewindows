<?php

use Illuminate\Support\Facades\File;

test('home keeps the regular copy and adds the Bing-only offer behind html.dw-bing', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain("html.classList.add('dw-bing')")
        ->toContain('<span class="bing-hide">40% Off — Limited Time</span>')
        ->toContain('40% Off on 3+ Windows or Doors')
        ->toContain('hero-form-offer-footer__note bing-hide">Best for projects with 4+ windows')
        ->toContain('40% OFF applies to projects with 3+ windows or doors')
        ->not->toContain('bing-offer-steps')
        ->not->toContain('Get 40% OFF the whole project')
        ->toContain('hero-brands--bing bing-only')
        ->toContain('class="bing-only ms-home-hero"')
        ->toContain('Window and door replacement in the Bay Area with professional installation.')
        ->toContain('Free in-home measure and a written estimate.')
        ->toContain('Get a Free Estimate')
        ->toContain('CA Lic. #695262')
        ->toContain('class="div-block-65 bing-only"')
        ->not->toContain('ms-home-hero__cert')
        ->toContain('data-ms-review-slot')
        ->toContain("badge.className = 'qty-pick__deal bing-only'");
});

test('a Google click always wins over a stored Bing click', function () {
    $script = File::get(resource_path('views/partials/bing-audience-head.blade.php'));

    $googleInUrl = strpos($script, "query.get('gclid')");
    $bingInUrl = strpos($script, "query.get('msclkid')");
    $googleStored = strpos($script, "stored('lead_param_gclid')");
    $bingStored = strpos($script, "stored('lead_param_msclkid')");

    expect($googleInUrl)->toBeLessThan($bingInUrl)
        ->and($googleStored)->toBeLessThan($bingStored);
});

test('Bing-only copy is hidden for everyone else', function () {
    $css = File::get(public_path('webflow-overrides/bing-offer.css'));

    expect($css)
        ->toContain("html:not(.dw-bing) .bing-only,\nhtml:not(.dw-bing) #trustBadgesBar .div-block-65.bing-only {\n  display: none !important;")
        ->toContain("html.dw-bing .bing-hide {\n  display: none !important;");
});

test('the 3-unit saving example uses the page promo price', function () {
    expect(bing_offer_savings(['base' => '832', 'final' => '$499']))
        ->toBe(['units' => 3, 'base' => 832, 'final' => 499, 'save' => 999])
        ->and(bing_offer_savings(['base' => '', 'final' => '$499']))->toBeNull()
        ->and(bing_offer_savings(['base' => '499', 'final' => '$499']))->toBeNull()
        ->and(bing_offer_savings(null))->toBeNull();
});
