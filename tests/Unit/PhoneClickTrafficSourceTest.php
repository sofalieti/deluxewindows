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

test('phone click keeps first and last traffic sources separately', function () {
    $click = new PhoneClick([
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'gclid' => 'last-gclid',
        'first_utm_source' => 'google',
        'first_utm_medium' => 'organic',
        'first_referrer' => 'https://www.google.com/search?q=windows',
    ]);

    expect($click->trafficSourceKey())->toBe('google_ads')
        ->and($click->trafficSourceLabel())->toBe('Google Ads')
        ->and($click->firstTrafficSourceKey())->toBe('seo_google')
        ->and($click->firstTrafficSourceLabel())->toBe('SEO · Google');
});
