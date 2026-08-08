<?php

declare(strict_types=1);

use App\Models\PhoneClick;
use App\Services\ServiceAreaRegions;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

test('every city maps to a region with a well formed local number', function () {
    $regions = app(ServiceAreaRegions::class);
    $cities = $regions->cities();

    expect($cities)->toHaveCount(98);

    $withRegion = 0;
    foreach ($cities as $slug => $city) {
        expect($city['name'] ?? '')->not->toBe('', "{$slug} has no display name");

        $region = $regions->forCitySlug($slug);
        if ($region === null) {
            continue;
        }

        $withRegion++;
        expect($region['phone_display'])->toMatch('/^\(\d{3}\) \d{3}-\d{4}$/')
            ->and($region['phone_tel'])->toMatch('/^\+1\d{10}$/');
    }

    expect($withRegion)->toBe(98);
});

test('cities land on the number their area code implies', function (string $slug, string $expected) {
    expect(app(ServiceAreaRegions::class)->forCitySlug($slug)['phone_display'])->toBe($expected);
})->with([
    'San Francisco is North Bay' => ['san-francisco', '(415) 651-2321'],
    'Sausalito is North Bay' => ['sausalito', '(415) 651-2321'],
    'Burlingame is Peninsula' => ['burlingame', '(650) 461-4446'],
    'Oakland is East Bay' => ['oakland', '(510) 244-6500'],
    'Richmond is East Bay despite Contra Costa' => ['richmond', '(510) 244-6500'],
    'Orinda is Lamorinda' => ['orinda', '(925) 430-5135'],
    'Walnut Creek is 925' => ['walnut-creek', '(925) 430-5135'],
    'Pleasanton is 925 despite Alameda' => ['pleasanton', '(925) 430-5135'],
    'San Jose is South Bay' => ['san-jose', '(408) 516-1200'],
    'Vallejo (Solano) is 925' => ['vallejo', '(925) 430-5135'],
    'Fairfield (Solano) is 925' => ['fairfield', '(925) 430-5135'],
    'Vacaville (Solano) is 925' => ['vacaville', '(925) 430-5135'],
]);

test('Solano County cities share the 925 Lamorinda number', function (string $slug) {
    expect(app(ServiceAreaRegions::class)->forCitySlug($slug)['phone_display'])->toBe('(925) 430-5135')
        ->and(service_area_phone($slug)['phone_tel'])->toBe('+19254305135');
})->with(['vallejo', 'fairfield', 'benicia', 'vacaville', 'dixon', 'rio-vista', 'suisun-city']);

test('utm_city resolves a Google geo criteria id to one of our cities', function () {
    $regions = app(ServiceAreaRegions::class);
    $geo = $regions->geoTargets(ServiceAreaRegions::GEO_GOOGLE);

    $id = array_search('fremont', $geo, true);
    expect($id)->toBeInt();

    $resolved = $regions->resolveUtmCity((string) $id, ServiceAreaRegions::GEO_GOOGLE);

    expect($resolved)->not->toBeNull()
        ->and($resolved['slug'])->toBe('fremont')
        ->and($resolved['name'])->toBe('Fremont')
        ->and($resolved['region']['phone_display'])->toBe('(510) 244-6500');
});

test('utm_city resolves a Google postal-code criteria id to the city', function () {
    // Google sometimes fills {loc_physical_ms} with a Postal Code criteria id
    // (9032015 = ZIP 94549) instead of the City id (1013919 = Lafayette).
    $regions = app(ServiceAreaRegions::class);
    $resolved = $regions->resolveUtmCity('9032015', ServiceAreaRegions::GEO_GOOGLE);

    expect($resolved)->not->toBeNull()
        ->and($resolved['slug'])->toBe('lafayette')
        ->and($resolved['name'])->toBe('Lafayette')
        ->and($regions->utmCityLabel('9032015', ServiceAreaRegions::GEO_GOOGLE))
        ->toBe('Lafayette — 9032015');

    // Burlingame ZIP 94010 and Cupertino ZIP 95014 are also mapped.
    $geo = $regions->geoTargets(ServiceAreaRegions::GEO_GOOGLE);
    expect(array_search('burlingame', $geo, true))->toBeInt()
        ->and($regions->resolveUtmCity((string) array_search('burlingame', $geo, true), ServiceAreaRegions::GEO_GOOGLE)['slug'])
        ->toBe('burlingame');
});

test('utm_city resolves a Bing location id through the Bing map', function () {
    $regions = app(ServiceAreaRegions::class);
    $geo = $regions->geoTargets(ServiceAreaRegions::GEO_BING);

    $id = array_search('vallejo', $geo, true);
    expect($id)->toBeInt()
        ->and($id)->toBe(43578);

    $resolved = $regions->resolveUtmCity((string) $id, ServiceAreaRegions::GEO_BING);

    expect($resolved)->not->toBeNull()
        ->and($resolved['slug'])->toBe('vallejo')
        ->and($resolved['region']['phone_display'])->toBe('(925) 430-5135');
});

test('traffic source picks which geo id space to prefer', function () {
    $regions = app(ServiceAreaRegions::class);

    expect($regions->platformFromAttribution(['msclkid' => 'abc']))->toBe(ServiceAreaRegions::GEO_BING)
        ->and($regions->platformFromAttribution(['gclid' => 'abc']))->toBe(ServiceAreaRegions::GEO_GOOGLE)
        ->and($regions->platformFromAttribution(['utm_source' => 'bing']))->toBe(ServiceAreaRegions::GEO_BING)
        ->and($regions->platformFromAttribution(['utm_source' => 'google']))->toBe(ServiceAreaRegions::GEO_GOOGLE);
});

test('utm_city also accepts a plain city name or slug', function (string $value) {
    $resolved = app(ServiceAreaRegions::class)->resolveUtmCity($value);

    expect($resolved)->not->toBeNull()
        ->and($resolved['slug'])->toBe('palo-alto')
        ->and($resolved['region']['phone_display'])->toBe('(650) 461-4446');
})->with(['palo-alto', 'Palo Alto', 'PALO ALTO']);

test('an unknown utm_city is reported rather than silently matched', function () {
    $regions = app(ServiceAreaRegions::class);

    expect($regions->resolveUtmCity('9999999'))->toBeNull()
        ->and($regions->resolveUtmCity('Sacramento'))->toBeNull()
        ->and($regions->utmCityLabel('9999999'))->toBe('9999999 (unmatched)')
        ->and($regions->utmCityLabel(''))->toBe('-');
});

test('the lookup endpoint only publishes cities that have a local number', function () {
    $response = get('/service-area-phones.json')->assertOk();

    $payload = $response->json();

    expect($payload['cities'])->toHaveCount(98)
        ->and(count($payload['geo_google']))->toBeGreaterThan(200)
        ->and($payload['geo_google'])->toHaveKey('9032015')
        ->and($payload['geo_google']['9032015'])->toBe('lafayette')
        ->and($payload['geo_bing'])->toHaveCount(98)
        ->and($payload['geo_bing']['43578'])->toBe('vallejo')
        ->and($payload['cities']['vallejo'])->toBe([
            'name' => 'Vallejo',
            'phone_display' => '(925) 430-5135',
            'phone_tel' => '+19254305135',
        ])
        ->and($payload['cities']['oakland'])->toBe([
            'name' => 'Oakland',
            'phone_display' => '(510) 244-6500',
            'phone_tel' => '+15102446500',
        ]);

    foreach ($payload['geo_bing'] as $slug) {
        expect($payload['cities'])->toHaveKey($slug);
    }

    $response->assertHeader('cache-control', 'max-age=86400, public, s-maxage=86400');
});

test('a city page advertises the local number in place of the general one', function () {
    $response = get('/window-replacement/oakland')->assertOk();

    $response->assertSee('tel:+15102446500', false)
        ->assertSee('(510) 244-6500', false)
        ->assertDontSee('Installation &amp; Replacement | Bay Area', false);
});

test('a Solano County city page advertises the 925 number', function () {
    $response = get('/window-replacement/vallejo')->assertOk();

    $response->assertSee('tel:+19254305135', false)
        ->assertSee('(925) 430-5135', false);
});

test('a phone click records utm_city and utm_redirect', function () {
    postJson('/phone-click', [
        'phone' => '+16504614446',
        'page_url' => 'https://www.deluxewindows.com/',
        'utm_source' => 'google',
        'utm_city' => '1013802',
        'utm_redirect' => 'yes',
        'first_utm_city' => '1013802',
    ])->assertOk()->assertJson(['ok' => true]);

    $click = PhoneClick::query()->latest('id')->firstOrFail();

    expect($click->utm_city)->toBe('1013802')
        ->and($click->utm_redirect)->toBe('yes')
        ->and($click->first_utm_city)->toBe('1013802')
        ->and(app(ServiceAreaRegions::class)->utmCityLabel($click->utm_city))
        ->toBe('Fremont — 1013802');
});
