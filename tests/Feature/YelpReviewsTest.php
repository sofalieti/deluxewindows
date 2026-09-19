<?php

use App\Services\YelpReviewsService;
use Illuminate\Support\Facades\File;

test('yelp reviews payload exposes the official rating and three-star-plus reviews', function () {
    $payload = app(YelpReviewsService::class)->payload();

    expect($payload)->not->toBeNull()
        ->and($payload['business']['rating'])->toBe(4.5)
        ->and($payload['business']['reviews_number'])->toBe(257)
        ->and($payload['business']['yelp_url'])->toContain('deluxe-windows-burlingame-3')
        ->and($payload['reviews'])->not->toBeEmpty();

    foreach ($payload['reviews'] as $review) {
        expect($review['rating'])->toBeGreaterThanOrEqual(3)
            ->and($review['text'])->not->toBeEmpty()
            ->and($review['author'])->not->toBeEmpty();
    }
});

test('yelp reviews partial renders rating, stars and review links', function () {
    $html = view('partials.yelp-reviews')->render();

    expect($html)
        ->toContain('data-yelp-reviews')
        ->toContain('4.5')
        ->toContain('257')
        ->toContain('https://www.yelp.com/biz/deluxe-windows-burlingame-3')
        ->toContain('dw-yelp__person')
        ->toContain('dw-yelp-stars')
        ->not->toContain('View on Yelp')
        ->not->toContain('elfsight-app-');
});

test('trust badges bar shows a Yelp rating badge and left reviews drawer', function () {
    $html = view('partials.trust-badges')->render();

    expect($html)
        ->toContain('data-yelp-badge')
        ->toContain('data-yelp-drawer')
        ->toContain('dw-yelp-stars')
        ->toContain('4.5')
        ->toContain('257')
        ->toContain('dw-yelp__person')
        ->not->toContain('View on Yelp')
        ->not->toContain('elfsight-app-e3dc666e')
        ->not->toContain('elfsightcdn.com');
});

test('content review blocks no longer mount Elfsight widgets', function () {
    $files = [
        resource_path('views/partials/reviews.blade.php'),
        resource_path('views/testimonials.blade.php'),
        resource_path('views/about.blade.php'),
        resource_path('views/window-replacement/show.blade.php'),
    ];

    foreach ($files as $file) {
        $contents = File::get($file);
        expect($contents)
            ->toContain('partials.yelp-reviews')
            ->not->toContain('elfsight-app-b6b258cb')
            ->not->toContain('elfsight-app-9b5ea9e5')
            ->not->toContain('elfsight-app-54d8cb68');
    }
});
