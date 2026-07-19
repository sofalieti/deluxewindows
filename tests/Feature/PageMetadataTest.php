<?php

use App\Services\Seo\PageMetadataRepository;
use App\Services\Seo\SchemaBuilder;

test('all committed page metadata files are valid and uniquely mapped', function () {
    $result = app(PageMetadataRepository::class)->validateAll();

    expect($result['invalid'])->toBe([])
        ->and($result['valid'])->toBeGreaterThan(150);
});

test('all SEO assignments are unique concise and English only', function () {
    $records = collect(\Illuminate\Support\Facades\File::allFiles(
        database_path('data/page-metadata')
    ))->filter(fn ($file) => $file->getExtension() === 'json')
        ->map(fn ($file) => json_decode(
            (string) file_get_contents($file->getPathname()),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));

    expect($records)->toHaveCount(228);

    foreach (['title', 'description', 'h1', 'primary_keyword'] as $field) {
        $values = $records->pluck("seo.{$field}");
        expect($values->filter())->toHaveCount(228)
            ->and($values->unique())->toHaveCount(228);
    }

    foreach ($records as $record) {
        $seo = $record['seo'];
        expect(mb_strlen($seo['title']))->toBeLessThanOrEqual(60)
            ->and(mb_strlen($seo['description']))->toBeGreaterThanOrEqual(140)
            ->and(mb_strlen($seo['description']))->toBeLessThanOrEqual(160)
            ->and(preg_match('/[А-Яа-яЁё]/u', json_encode($seo)))->toBe(0)
            ->and($seo['robots'])->toContain('index,follow')
            ->and($seo['target_keywords'])->not->toBeEmpty();

        foreach (['og', 'twitter'] as $group) {
            $image = $seo[$group]['image'] ?? null;
            if (! is_string($image) || $image === '') {
                continue;
            }

            expect($image)->toStartWith('/webflow-assets/images/')
                ->and(public_path(ltrim($image, '/')))->toBeFile();
        }
    }
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
        ->and($faqSchema['mainEntity'])->toHaveCount(count($metadata->faq))
        ->and($markup)->toContain('faqs-grid-v3')
        ->and($markup)->toContain('accordion-card v2')
        ->and($markup)->toContain('accordion-item-wrapper v2');

    foreach ($metadata->faq as $item) {
        expect($markup)->toContain(e($item['question']))
            ->and($markup)->toContain(e($item['answer']));
    }
});

test('all public faq content is unique and file backed', function () {
    $records = collect(\Illuminate\Support\Facades\File::allFiles(
        database_path('data/page-metadata')
    ))->filter(fn ($file) => $file->getExtension() === 'json')
        ->map(fn ($file) => json_decode(
            (string) file_get_contents($file->getPathname()),
            true,
            512,
            JSON_THROW_ON_ERROR
        ));

    $excludedPaths = [
        '/about',
        '/blog',
        '/brand',
        '/gallery',
        '/glossary',
        '/testimonials',
    ];
    $withFaq = $records->filter(fn (array $record): bool => $record['faq'] !== []);
    $faqItems = $withFaq->flatMap(fn (array $record): array => $record['faq']);
    $questions = $faqItems->pluck('question');
    $answers = $faqItems->pluck('answer');

    expect($withFaq)->toHaveCount(222)
        ->and($records->whereIn('path', $excludedPaths)->pluck('faq')->filter())
        ->toBeEmpty()
        ->and($questions)->toHaveCount(1107)
        ->and($questions->map(fn (string $value): string => mb_strtolower($value))->unique())
        ->toHaveCount(1107)
        ->and($answers->map(fn (string $value): string => mb_strtolower($value))->unique())
        ->toHaveCount(1107)
        ->and(preg_match(
            '/[А-Яа-яЁё]/u',
            json_encode([$questions, $answers], JSON_UNESCAPED_UNICODE)
        ))->toBe(0);

    foreach ([
        resource_path('views/windows/show.blade.php'),
        resource_path('views/doors/show.blade.php'),
        resource_path('views/financing.blade.php'),
        resource_path('views/windows-show.blade.php'),
    ] as $viewPath) {
        expect(file_get_contents($viewPath))
            ->not->toContain('Which material is best for your windows?')
            ->not->toContain('Do You Have Any Question?');
    }

    expect(file_get_contents(app_path('Models/DoorBrand.php')))
        ->not->toContain("'faq'")
        ->not->toContain('faqItems');
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
        ->toContain('<meta name="robots" content="'.$metadata->robots.'"')
        ->toContain('rel="canonical" href="'.$metadata->canonical.'"')
        ->toContain('name="twitter:title" content="'.e($metadata->twitterTitle).'"')
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
