<?php

use App\Models\Lead;

test('lead traffic source is classified from click ids utm data and referrers', function (
    array $attributes,
    string $expectedKey,
    string $expectedLabel
) {
    $lead = new Lead($attributes);

    expect($lead->trafficSourceKey())->toBe($expectedKey)
        ->and($lead->trafficSourceLabel())->toBe($expectedLabel);
})->with([
    'Google Ads by GCLID' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
        'meta' => ['gclid' => 'test-click-id'],
    ], 'google_ads', 'Google Ads'],
    'Google Ads by UTM' => [[
        'utm_source' => 'google',
        'utm_medium' => 'cpc',
    ], 'google_ads', 'Google Ads'],
    'Microsoft Ads' => [[
        'utm_source' => 'bing',
        'utm_medium' => 'cpc',
        'meta' => ['msclkid' => 'test-ms-click-id'],
    ], 'microsoft_ads', 'Microsoft Ads'],
    'Meta Ads' => [[
        'utm_source' => 'facebook',
        'utm_medium' => 'paid_social',
        'meta' => ['fbclid' => 'test-fb-click-id'],
    ], 'meta_ads', 'Meta Ads'],
    'Google organic search' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
        'meta' => ['referrer' => 'https://www.google.com/search?q=windows'],
    ], 'seo_google', 'SEO · Google'],
    'Bing organic search' => [[
        'meta' => ['referrer' => 'https://www.bing.com/search?q=windows'],
    ], 'seo_bing', 'SEO · Bing'],
    'external referral' => [[
        'meta' => ['referrer' => 'https://example.com/recommended-contractors'],
    ], 'referral', 'Referral'],
    'direct visit' => [[
        'utm_source' => '(direct)',
        'utm_medium' => '(none)',
        'meta' => [],
    ], 'direct', 'Direct'],
]);
