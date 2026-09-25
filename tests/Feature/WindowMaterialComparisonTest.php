<?php

use App\Services\WindowMaterialComparisonService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

test('window material comparison dataset is complete and valid', function () {
    $result = app(WindowMaterialComparisonService::class)->validate();

    expect($result['valid'])->toBeTrue()
        ->and($result['materials'])->toBe(7)
        ->and($result['criteria'])->toBe(10)
        ->and($result['errors'])->toBe([]);
});

test('every window material resolves two unique comparison peers', function (string $slug) {
    $comparison = app(WindowMaterialComparisonService::class)->forSlug($slug);

    expect($comparison)->not->toBeNull()
        ->and($comparison['current_slug'])->toBe($slug)
        ->and($comparison['peer_slugs'])->toHaveCount(2)
        ->and(array_unique($comparison['peer_slugs']))->toHaveCount(2)
        ->and($comparison['peer_slugs'])->not->toContain($slug)
        ->and($comparison['criteria'])->toHaveCount(10);

    foreach ($comparison['materials'] as $materialSlug => $material) {
        expect($material['url'])->toBe("/windows/{$materialSlug}")
            ->and($material['values'])->toHaveCount(10)
            ->and($material['pros'])->toHaveCount(4)
            ->and($material['cons'])->toHaveCount(4);

        foreach ($material['values'] as $value) {
            expect($value['rating'])->toBeGreaterThanOrEqual(1)
                ->and($value['rating'])->toBeLessThanOrEqual(5)
                ->and($value['label'])->not->toBeEmpty()
                ->and($value['detail'])->not->toBeEmpty();
        }
    }
})->with([
    'vinyl-windows',
    'fiberglass-windows',
    'wood-windows',
    'wood-clad-windows',
    'aluminum-windows',
    'aluminum-clad-windows',
    'steel-windows',
]);

test('comparison partial renders an accessible server-side initial state', function () {
    $comparison = app(WindowMaterialComparisonService::class)->forSlug('vinyl-windows');
    $html = view('partials.window-material-comparison', compact('comparison'))->render();

    expect($html)
        ->toContain('Compare Window Materials')
        ->toContain('Vinyl: Pros &amp; Cons')
        ->toContain('Fiberglass: Pros &amp; Cons')
        ->toContain('Wood Clad: Pros &amp; Cons')
        ->toContain('Frame material is only one part of window performance.')
        ->toContain('type="application/json"')
        ->toContain('data-wmc-tradeoff-link')
        ->toContain('data-open-estimate-modal')
        ->toContain('data-wmc-expand')
        ->toContain('Compare all points')
        ->toContain('aria-live="polite"')
        ->not->toContain('href="#contact"')
        ->not->toContain('<h1')
        ->and(substr_count($html, 'data-wmc-select'))->toBe(2)
        ->and(substr_count($html, 'data-wmc-criterion='))->toBe(10);
});

test('comparison is placed after the gallery and collapses to one peer on mobile', function () {
    $template = File::get(resource_path('views/windows/show.blade.php'));
    $css = File::get(public_path('webflow-overrides/window-detail.css'));

    $galleryPosition = strpos($template, 'id="dw-gallery"');
    $comparisonPosition = strpos($template, "partials.window-material-comparison");

    expect($galleryPosition)->toBeInt()
        ->and($comparisonPosition)->toBeInt()
        ->and($comparisonPosition)->toBeGreaterThan($galleryPosition)
        ->and($css)->toContain('.wmc__material-header[data-slot="peer-2"]')
        ->and($css)->toContain('.wmc__value[data-slot="peer-2"]')
        ->and($css)->toContain('.wmc__tradeoff-card[data-slot="peer-2"]');
});

test('comparison partial escapes visible and embedded dataset content', function () {
    $comparison = app(WindowMaterialComparisonService::class)->forSlug('vinyl-windows');
    $unsafe = '<script>alert("comparison")</script>';
    $comparison['current']['tagline'] = $unsafe;
    $comparison['materials']['vinyl-windows']['tagline'] = $unsafe;

    $html = view('partials.window-material-comparison', compact('comparison'))->render();

    expect($html)
        ->not->toContain($unsafe)
        ->toContain('&lt;script&gt;alert')
        ->toContain('\\u003Cscript\\u003Ealert');
});

test('invalid comparison data fails safely and logs a warning', function () {
    $path = storage_path('framework/testing/invalid-window-material-comparison.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '{"version":999}');
    Log::spy();

    $service = new class($path) extends WindowMaterialComparisonService
    {
        public function __construct(private readonly string $testPath) {}

        public function path(): string
        {
            return $this->testPath;
        }
    };

    try {
        expect($service->forSlug('vinyl-windows'))->toBeNull();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message ===
                'Window material comparison data could not be loaded'
                && $context['slug'] === 'vinyl-windows');
    } finally {
        File::delete($path);
    }
});
