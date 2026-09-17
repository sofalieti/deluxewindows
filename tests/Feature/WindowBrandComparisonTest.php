<?php

use App\Services\WindowBrandComparisonService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

test('window brand comparison dataset is complete and valid', function () {
    $result = app(WindowBrandComparisonService::class)->validate();

    expect($result['valid'])->toBeTrue()
        ->and($result['brands'])->toBe(11)
        ->and($result['criteria'])->toBe(9)
        ->and($result['errors'])->toBe([]);
});

test('every window brand resolves two unique comparison peers', function (string $slug) {
    $comparison = app(WindowBrandComparisonService::class)->forSlug($slug);

    expect($comparison)->not->toBeNull()
        ->and($comparison['current_slug'])->toBe($slug)
        ->and($comparison['peer_slugs'])->toHaveCount(2)
        ->and(array_unique($comparison['peer_slugs']))->toHaveCount(2)
        ->and($comparison['peer_slugs'])->not->toContain($slug)
        ->and($comparison['criteria'])->toHaveCount(9);

    $criterionTypes = collect($comparison['criteria'])->pluck('type', 'key');

    foreach ($comparison['brands'] as $brandSlug => $brand) {
        expect($brand['url'])->toBe("/brands/{$brandSlug}")
            ->and($brand['values'])->toHaveCount(9)
            ->and($brand['pros'])->toHaveCount(3)
            ->and($brand['considerations'])->toHaveCount(3)
            ->and($brand['sources'])->not->toBeEmpty();

        foreach ($brand['sources'] as $source) {
            expect($source['url'])->toStartWith('https://');
        }

        foreach ($brand['values'] as $key => $value) {
            expect($value['label'])->not->toBeEmpty()
                ->and($value['detail'])->not->toBeEmpty();

            if ($criterionTypes[$key] === 'score') {
                expect($value['rating'])->toBeGreaterThanOrEqual(1)
                    ->and($value['rating'])->toBeLessThanOrEqual(5);
            } else {
                expect($value)->not->toHaveKey('rating');
            }
        }
    }
})->with([
    'marvin',
    'milgard',
    'anlin',
    'andersen',
    'jeld-wen',
    'ply-gem',
    'simonton',
    'alside',
    'western-window-systems',
    'all-weather-architectural-aluminum',
    'italwindows',
]);

test('brand comparison partial renders a safe server-side initial state', function () {
    $comparison = app(WindowBrandComparisonService::class)->forSlug('marvin');
    $html = view('partials.window-brand-comparison', compact('comparison'))->render();

    expect($html)
        ->toContain('Compare Window Brands')
        ->toContain('Marvin: Pros &amp; Considerations')
        ->toContain('Andersen: Pros &amp; Considerations')
        ->toContain('Milgard: Pros &amp; Considerations')
        ->toContain('This is a brand-level guide')
        ->toContain('type="application/json"')
        ->toContain('data-wbc-tradeoff-link')
        ->toContain('aria-live="polite"')
        ->not->toContain('<h1')
        ->and(substr_count($html, 'data-wbc-select'))->toBe(2)
        ->and(substr_count($html, 'data-wbc-criterion='))->toBe(9);
});

test('brand comparison is placed immediately after the gallery slot', function () {
    $template = File::get(resource_path('views/brands/show.blade.php'));
    $css = File::get(public_path('webflow-overrides/window-brand-comparison.css'));
    $javascript = File::get(public_path('webflow-overrides/window-brand-comparison.js'));

    $galleryPosition = strpos(
        $template,
        '<div class="image-wrapper border-radius-image-default"></div>'
    );
    $comparisonPosition = strpos($template, "partials.window-brand-comparison");
    $windowTypesPosition = strpos($template, '@if($windowTypes->count() > 0)');

    expect($galleryPosition)->toBeInt()
        ->and($comparisonPosition)->toBeInt()
        ->and($windowTypesPosition)->toBeInt()
        ->and($comparisonPosition)->toBeGreaterThan($galleryPosition)
        ->and($comparisonPosition)->toBeLessThan($windowTypesPosition)
        ->and($css)->toContain('.wbc__brand-header[data-slot="peer-2"]')
        ->and($css)->toContain('.wbc__value[data-slot="peer-2"]')
        ->and($javascript)->toContain('select.addEventListener("input"')
        ->and($javascript)->toContain('select.addEventListener("change"');
});

test('brand comparison escapes visible and embedded dataset content', function () {
    $comparison = app(WindowBrandComparisonService::class)->forSlug('marvin');
    $unsafe = '<script>alert("brand-comparison")</script>';
    $comparison['current']['tagline'] = $unsafe;
    $comparison['brands']['marvin']['tagline'] = $unsafe;

    $html = view('partials.window-brand-comparison', compact('comparison'))->render();

    expect($html)
        ->not->toContain($unsafe)
        ->toContain('&lt;script&gt;alert')
        ->toContain('\\u003Cscript\\u003Ealert');
});

test('invalid brand comparison data fails safely and logs a warning', function () {
    $path = storage_path('framework/testing/invalid-window-brand-comparison.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '{"version":999}');
    Log::spy();

    $service = new class($path) extends WindowBrandComparisonService
    {
        public function __construct(private readonly string $testPath) {}

        public function path(): string
        {
            return $this->testPath;
        }
    };

    try {
        expect($service->forSlug('marvin'))->toBeNull();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message ===
                'Window brand comparison data could not be loaded'
                && $context['slug'] === 'marvin');
    } finally {
        File::delete($path);
    }
});
