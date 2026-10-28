<?php

use Illuminate\Http\Request;

function familyPreviewFor(string $path): bool
{
    app()->instance('request', Request::create($path));

    return family_preview();
}

test('family overlay is only on material and brand pages with the query', function () {
    expect(familyPreviewFor('/windows/vinyl-windows?family=true'))->toBeTrue()
        ->and(familyPreviewFor('/windows?family=true'))->toBeTrue()
        ->and(familyPreviewFor('/doors/vinyl-doors?family=true'))->toBeTrue()
        ->and(familyPreviewFor('/brands/marvin?family=true'))->toBeTrue()
        ->and(familyPreviewFor('/door-brands/marvin?family=1'))->toBeTrue()
        ->and(familyPreviewFor('/window-type/marvin-vinyl?family=yes'))->toBeTrue()
        ->and(familyPreviewFor('/door-types/marvin-vinyl?family=on'))->toBeTrue()
        ->and(familyPreviewFor('/windows/vinyl-windows'))->toBeFalse()
        ->and(familyPreviewFor('/windows/vinyl-windows?family=false'))->toBeFalse()
        ->and(familyPreviewFor('/?family=true'))->toBeFalse()
        ->and(familyPreviewFor('/about?family=true'))->toBeFalse();

    expect(is_file(public_path('webflow-assets/images/family-cutout.webp')))->toBeTrue();

    $css = file_get_contents(public_path('webflow-overrides/site-custom.css'));
    expect($css)->toContain('html.dw-family')
        ->and($css)->toContain('family-cutout.webp')
        ->and($css)->toContain('.is-brand-logo');
});

test('the homepage does not get the family class', function () {
    $html = $this->get('/?family=true')->assertOk()->getContent();

    expect($html)->not->toContain('dw-family');
});
