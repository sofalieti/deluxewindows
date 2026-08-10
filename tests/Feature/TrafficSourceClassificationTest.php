<?php

declare(strict_types=1);

use App\Models\PhoneClick;

test('paid bing utm wins over a stale gclid from a prior google visit', function () {
    $click = new PhoneClick([
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'Local & Replacement Intent (Bay Area)',
        'gclid' => 'old-google-click',
        'msclkid' => 'b5c465e54e9e1dd614870f9d8533fe15',
    ]);

    expect($click->trafficSourceKey())->toBe('microsoft_ads')
        ->and($click->trafficSourceLabel())->toBe('Microsoft Ads');
});

test('gclid still classifies as google ads when utm is google paid', function () {
    $click = new PhoneClick([
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
        'gclid' => 'fresh-google-click',
    ]);

    expect($click->trafficSourceKey())->toBe('google_ads');
});

test('msclkid alone classifies as microsoft ads', function () {
    $click = new PhoneClick([
        'utm_source' => '',
        'utm_medium' => '',
        'msclkid' => 'ms-only',
    ]);

    expect($click->trafficSourceKey())->toBe('microsoft_ads');
});
