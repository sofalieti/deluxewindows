<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'webflow' => [
        'api_base_url' => env('WEBFLOW_API_BASE_URL', 'https://api.webflow.com/v2'),
        'api_token' => env('WEBFLOW_API_TOKEN'),
        'site_id' => env('WEBFLOW_SITE_ID'),
    ],

    'google' => [
        'ga4_id' => env('GOOGLE_GA4_ID', 'G-JHYBB0THJM'),
        'ads_id' => env('GOOGLE_ADS_ID', 'AW-1030787786'),
        'conversion_send_to' => env('GOOGLE_ADS_CONVERSION_SEND_TO', 'AW-1030787786/Hs9eCP7MwngQyqXC6wM'),
    ],

    'lead_bridge' => [
        'urls' => array_values(array_filter([
            env('LEAD_BRIDGE_URL_1', 'https://script.google.com/macros/s/AKfycbyJGhNROpBI8TUkGn9RtdNtIDxNjxsI52kyHgBtDIUauSEWgzVIqCFPic0-chwjxNxU/exec'),
            env('LEAD_BRIDGE_URL_2', 'https://script.google.com/macros/s/AKfycbwp7eg4fm8OZtiHLjAFrbNyPaSyDjZWmfTJyhkiAZ2UsWYmE6l7euH9K0RtdgODH44Rmg/exec'),
        ])),
    ],

];
