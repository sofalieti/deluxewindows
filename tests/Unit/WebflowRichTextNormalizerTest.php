<?php

use App\Services\Webflow\WebflowRichTextNormalizer;

test('embedded h1 blocks are removed with their content', function () {
    $html = '<h1 id="intro" class="title">Why Choose Deluxe Windows</h1><h3>Benefits</h3>';

    expect(WebflowRichTextNormalizer::removeEmbeddedH1($html))
        ->toBe('<h3>Benefits</h3>');
});

test('embedded h1 tags are removed recursively from collection item data', function () {
    $items = [
        [
            'fieldData' => [
                'property-listing---about' => '<H1>Main description</H1><p>Copy</p>',
                'nested' => ['<h1>Nested description</h1>'],
            ],
        ],
    ];

    $normalized = WebflowRichTextNormalizer::removeEmbeddedH1($items);
    $encoded = json_encode($normalized, JSON_THROW_ON_ERROR);

    expect($encoded)
        ->not->toMatch('/<h1\b|<\/h1>/i')
        ->and($normalized[0]['fieldData']['property-listing---about'])
        ->toBe('<p>Copy</p>')
        ->and($normalized[0]['fieldData']['nested'][0])
        ->toBe('');
});

test('exported collection datasets contain no embedded h1 tags', function () {
    $root = dirname(__DIR__, 2).'/webflow-data/current';
    $files = [
        ...glob($root.'/collections/*/items*.json'),
        ...glob($root.'/imports/*.json'),
    ];

    expect($files)->not->toBeEmpty();

    foreach ($files as $file) {
        $contents = (string) file_get_contents($file);

        expect($contents)
            ->not->toMatch('/<h1\b|<\/h1>/i', "Embedded H1 found in {$file}");
    }
});
