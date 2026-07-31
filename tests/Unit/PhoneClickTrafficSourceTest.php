<?php

use App\Models\PhoneClick;

test('phone click traffic source is classified like leads', function (
    array $attributes,
    string $expectedKey,
    string $expectedLabel
) {
    $click = new PhoneClick($attributes);

    expect($click->trafficSourceKey())->toBe($expectedKey)
        ->and($click->trafficSourceLabel())->toBe($expectedLabel);
})->with([
    'Google Ads by GCLID' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
        'gclid' => 'test-click-id',
    ], 'google_ads', 'Google Ads'],
    'Google Ads by UTM' => [[
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
    ], 'google_ads', 'Google Ads'],
    'Google organic search' => [[
        'utm_source' => 'google',
        'utm_medium' => 'organic',
        'referrer' => 'https://www.google.com/search?q=windows',
    ], 'seo_google', 'SEO · Google'],
    'Google organic by referrer only' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
        'referrer' => 'https://www.google.com/search?q=windows',
    ], 'seo_google', 'SEO · Google'],
    'direct visit' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
    ], 'direct', 'Direct'],
]);
