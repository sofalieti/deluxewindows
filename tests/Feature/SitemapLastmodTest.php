<?php

use App\Services\SitemapGeneratorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

test('sitemap lastmod is never older than today and prefers newer cms dates', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-23 15:00:00', 'America/Los_Angeles'));

    $service = app(SitemapGeneratorService::class);
    $method = new ReflectionMethod(SitemapGeneratorService::class, 'resolveLastmod');
    $method->setAccessible(true);

    $old = new class extends Model {
        protected $guarded = [];
    };
    $old->forceFill([
        'webflow_updated_on' => '2026-03-23 12:00:00',
        'updated_at' => '2026-03-20 12:00:00',
    ]);

    $fresh = new class extends Model {
        protected $guarded = [];
    };
    $fresh->forceFill([
        'webflow_updated_on' => '2026-03-23 12:00:00',
        'updated_at' => '2026-07-23 08:00:00',
    ]);

    expect($method->invoke($service, null))->toBe('2026-07-23')
        ->and($method->invoke($service, $old))->toBe('2026-07-23')
        ->and($method->invoke($service, $fresh))->toBe('2026-07-23');

    Carbon::setTestNow();
});
