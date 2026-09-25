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

test('trust badges bar mounts the original Elfsight reviews widget', function () {
    $html = view('partials.trust-badges')->render();

    expect($html)
        ->toContain('elfsight-app-e3dc666e-7803-4c6a-94c1-0e4f1155d816')
        ->toContain('elfsightcdn.com')
        ->not->toContain('data-yelp-drawer');
});

test('content pages mount the original Elfsight Yelp review widgets', function () {
    expect(File::get(resource_path('views/partials/reviews.blade.php')))
        ->toContain('elfsight-app-b6b258cb-48f2-4f37-a4c4-f938938bbe24');

    expect(File::get(resource_path('views/about.blade.php')))
        ->toContain('elfsight-app-9b5ea9e5-b8e2-46ee-a99c-1e6552b85f66');

    expect(File::get(resource_path('views/testimonials.blade.php')))
        ->toContain('elfsight-app-9b5ea9e5-b8e2-46ee-a99c-1e6552b85f66');

    expect(File::get(resource_path('views/window-replacement/show.blade.php')))
        ->toContain('elfsight-app-54d8cb68-4afb-4ebe-b139-2bd0bc687876');
});
