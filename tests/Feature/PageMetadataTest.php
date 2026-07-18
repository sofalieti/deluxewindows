<?php

use App\Services\Seo\PageMetadataRepository;
use App\Services\Seo\SchemaBuilder;

test('all committed page metadata files are valid and uniquely mapped', function () {
    $result = app(PageMetadataRepository::class)->validateAll();

    expect($result['invalid'])->toBe([])
        ->and($result['valid'])->toBeGreaterThan(150);
});

test('representative public page families resolve metadata and schema from files', function (
    string $path,
    string $expectedType
) {
    $metadata = app(PageMetadataRepository::class)->forPath($path);
    $schemas = app(SchemaBuilder::class)->build($metadata);

    expect($metadata->key)->not->toBe('fallback')
        ->and($metadata->title)->not->toBeEmpty()
        ->and($metadata->description)->not->toBeEmpty()
        ->and($metadata->canonical)->toStartWith('https://www.deluxewindows.com')
        ->and(collect($schemas)->pluck('@type'))->toContain('BreadcrumbList')
        ->and(collect($schemas)->pluck('@type'))->toContain($expectedType);
})->with([
    'home' => ['/', 'WebSite'],
    'catalog product' => ['/windows/vinyl-windows', 'Product'],
    'blog post' => ['/blog/how-to-measure-windows-for-replacement', 'BlogPosting'],
    'county hub' => ['/county-hub-pages/alameda-county', 'CollectionPage'],
    'service area' => ['/window-replacement/san-jose', 'Service'],
]);

test('faq markup and faq schema use the same file entries', function () {
    $metadata = app(PageMetadataRepository::class)->forPath('/window-replacement/san-jose');
    $schemas = app(SchemaBuilder::class)->build($metadata);
    $faqSchema = collect($schemas)->firstWhere('@type', 'FAQPage');
    $markup = view('partials.page-metadata-faq', [
        'pageMetadata' => $metadata,
    ])->render();

    expect($metadata->faq)->not->toBeEmpty()
        ->and($faqSchema)->toBeArray()
        ->and($faqSchema['mainEntity'])->toHaveCount(count($metadata->faq));

    foreach ($metadata->faq as $item) {
        expect($markup)->toContain(e($item['question']))
            ->and($markup)->toContain(e($item['answer']));
    }
});

test('shared head renders canonical metadata and valid json ld', function () {
    $metadata = app(PageMetadataRepository::class)->forPath('/blog/how-to-measure-windows-for-replacement');
    $schemas = app(SchemaBuilder::class)->build($metadata);
    $head = view('partials.seo-head', [
        'pageMetadata' => $metadata,
        'pageSchemas' => $schemas,
    ])->render();

    expect($head)
        ->toContain('<title>'.e($metadata->title).'</title>')
        ->toContain('rel="canonical" href="'.$metadata->canonical.'"')
        ->toContain('"@type":"BlogPosting"');

    preg_match_all(
        '/<script type="application\/ld\+json">(.*?)<\/script>/s',
        $head,
        $matches
    );
    expect($matches[1])->not->toBeEmpty();
    foreach ($matches[1] as $json) {
        expect(json_decode($json, true, 512, JSON_THROW_ON_ERROR))->toBeArray();
    }
});

test('missing metadata fails safely without emitting file-derived schema', function () {
    $metadata = app(PageMetadataRepository::class)->forPath('/missing-page-metadata-test');
    $schemas = app(SchemaBuilder::class)->build($metadata);

    expect($metadata->key)->toBe('fallback')
        ->and($metadata->faq)->toBe([])
        ->and(collect($schemas)->pluck('@type'))->not->toContain('FAQPage')
        ->and($metadata->canonical)->toBe(
            'https://www.deluxewindows.com/missing-page-metadata-test'
        );
});

test('public controller no longer reads legacy metadata fields', function () {
    $controller = file_get_contents(app_path('Http/Controllers/ClassicSiteController.php'));

    expect($controller)
        ->not->toContain("'seo-title'")
        ->not->toContain("'seo-description'")
        ->not->toContain("'opengraph-title'")
        ->not->toContain("'opengraph-description'")
        ->not->toContain("'meta-title'")
        ->not->toContain("'meta-description'")
        ->not->toContain("'schema-json'");
});
