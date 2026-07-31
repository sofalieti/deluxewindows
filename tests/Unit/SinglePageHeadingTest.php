<?php

test('shared public page templates contain only their intended primary h1', function (
    string $relativePath,
    int $expectedLiteralH1Count
) {
    $path = dirname(__DIR__, 2).'/resources/views/'.$relativePath;
    $contents = (string) file_get_contents($path);

    expect(preg_match_all('/<h1\b/i', $contents))
        ->toBe($expectedLiteralH1Count);
})->with([
    'about contact section' => ['partials/about-contact-section.blade.php', 0],
    'special offers contact section' => ['partials/special-offers-contact-section.blade.php', 0],
    'blog contact section' => ['partials/blog-page-bottom.blade.php', 0],
    'financing contact section' => ['partials/contacts-webflow-section.blade.php', 0],
    'brand page' => ['brands/show.blade.php', 1],
    'door brand page' => ['door-brands/show.blade.php', 1],
    'window type page' => ['window-types/show.blade.php', 1],
    'door type page' => ['door-types/show.blade.php', 1],
    'window page uses shared seo h1' => ['windows/show.blade.php', 0],
    'door page uses shared seo h1' => ['doors/show.blade.php', 0],
]);
