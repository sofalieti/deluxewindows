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
        // Mailbox OAuth — values are normally stored in admin Mailbox settings;
        // env can still override for local/dev if needed.
        'client_id' => env('GOOGLE_MAILBOX_CLIENT_ID'),
        'client_secret' => env('GOOGLE_MAILBOX_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_MAILBOX_REDIRECT_URI'),
    ],

    /*
     * Offline conversion upload for phone clicks confirmed by a RingCentral call.
     * Each block is only used when every required credential is present.
     */
    'google_ads' => [
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_ADS_REFRESH_TOKEN'),
        'customer_id' => env('GOOGLE_ADS_CUSTOMER_ID'),
        'login_customer_id' => env('GOOGLE_ADS_LOGIN_CUSTOMER_ID'),
        'phone_conversion_action' => env('GOOGLE_ADS_PHONE_CONVERSION_ACTION'),
        // Exact conversion action name for the official GCLID import sheet template.
        'phone_conversion_name' => env('GOOGLE_ADS_PHONE_CONVERSION_NAME', 'Phone Call Confirmed'),
        'oauth_redirect_uri' => env('GOOGLE_ADS_OAUTH_REDIRECT_URI', 'http://localhost'),
        'api_version' => env('GOOGLE_ADS_API_VERSION', 'v25'),
        'api_base_url' => rtrim((string) env('GOOGLE_ADS_API_BASE_URL', 'https://googleads.googleapis.com'), '/'),
        'oauth_token_url' => env('GOOGLE_ADS_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
    ],

    /*
     * Daily / on-demand Google Drive export of confirmed Google Ads phone clicks
     * in the official Offline Conversion Import spreadsheet template.
     * Prefer a service account JSON whose email has Editor on the Drive folder.
     */
    'google_drive' => [
        'auth' => env('GOOGLE_DRIVE_AUTH', 'service_account'), // service_account | oauth
        'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID', '1rCfaF8tk29fPPdO0zljjRXyMIp4PLALv'),
        // Optional: pin one workbook. If empty, find/create "Google Ads Offline Conversions" in the folder.
        'spreadsheet_id' => env('GOOGLE_DRIVE_SPREADSHEET_ID'),
        'service_account_json' => env('GOOGLE_DRIVE_SERVICE_ACCOUNT_JSON'),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID', env('GOOGLE_ADS_CLIENT_ID')),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET', env('GOOGLE_ADS_CLIENT_SECRET')),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'oauth_token_url' => env('GOOGLE_DRIVE_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'timezone' => env('GOOGLE_DRIVE_EXPORT_TIMEZONE', 'America/Los_Angeles'),
        'drive_api_base_url' => rtrim((string) env('GOOGLE_DRIVE_API_BASE_URL', 'https://www.googleapis.com/drive/v3'), '/'),
        'sheets_api_base_url' => rtrim((string) env('GOOGLE_SHEETS_API_BASE_URL', 'https://sheets.googleapis.com/v4'), '/'),
    ],

    'microsoft_ads' => [
        'developer_token' => env('MICROSOFT_ADS_DEVELOPER_TOKEN'),
        'client_id' => env('MICROSOFT_ADS_CLIENT_ID'),
        'client_secret' => env('MICROSOFT_ADS_CLIENT_SECRET'),
        'refresh_token' => env('MICROSOFT_ADS_REFRESH_TOKEN'),
        'customer_id' => env('MICROSOFT_ADS_CUSTOMER_ID'),
        'account_id' => env('MICROSOFT_ADS_ACCOUNT_ID'),
        'phone_conversion_name' => env('MICROSOFT_ADS_PHONE_CONVERSION_NAME', 'Phone Call Confirmed'),
        // Which identity signs in to Microsoft Advertising: "microsoft" or "google".
        // Accounts created through "Sign in with Google" must use google, otherwise
        // the API rejects the token with IdentityTypeMismatch.
        'identity_provider' => env('MICROSOFT_ADS_IDENTITY_PROVIDER', 'microsoft'),
        'oauth_redirect_uri' => env('MICROSOFT_ADS_OAUTH_REDIRECT_URI'),
        'api_base_url' => rtrim((string) env(
            'MICROSOFT_ADS_API_BASE_URL',
            'https://campaign.api.bingads.microsoft.com'
        ), '/'),
        // Customer Management lives on its own host; used to verify the account id.
        'customer_api_base_url' => rtrim((string) env(
            'MICROSOFT_ADS_CUSTOMER_API_BASE_URL',
            'https://clientcenter.api.bingads.microsoft.com'
        ), '/'),
        'oauth_token_url' => env(
            'MICROSOFT_ADS_OAUTH_TOKEN_URL',
            'https://login.microsoftonline.com/common/oauth2/v2.0/token'
        ),
        'oauth_authorize_url' => env(
            'MICROSOFT_ADS_OAUTH_AUTHORIZE_URL',
            'https://login.microsoftonline.com/common/oauth2/v2.0/authorize'
        ),
        'google_oauth_token_url' => env(
            'MICROSOFT_ADS_GOOGLE_TOKEN_URL',
            'https://oauth2.googleapis.com/token'
        ),
        'google_oauth_authorize_url' => env(
            'MICROSOFT_ADS_GOOGLE_AUTHORIZE_URL',
            'https://accounts.google.com/o/oauth2/v2/auth'
        ),
    ],

    'lead_bridge' => [
        'urls' => array_values(array_filter([
            env('LEAD_BRIDGE_URL_1', 'https://script.google.com/macros/s/AKfycbyJGhNROpBI8TUkGn9RtdNtIDxNjxsI52kyHgBtDIUauSEWgzVIqCFPic0-chwjxNxU/exec'),
            env('LEAD_BRIDGE_URL_2', 'https://script.google.com/macros/s/AKfycbwp7eg4fm8OZtiHLjAFrbNyPaSyDjZWmfTJyhkiAZ2UsWYmE6l7euH9K0RtdgODH44Rmg/exec'),
        ])),
    ],

    'lead_notifications' => [
        // Comma-separated list of recipients for the "new lead" email.
        'to' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('LEAD_NOTIFICATION_EMAIL', 'info@deluxewindows.com'))
        ))),
    ],

    'ringcentral' => [
        'base_url' => rtrim((string) env('RINGCENTRAL_BASE_URL', 'https://platform.ringcentral.com'), '/'),
        'media_url' => rtrim((string) env('RINGCENTRAL_MEDIA_URL', ''), '/'),
        'client_id' => env('RINGCENTRAL_CLIENT_ID'),
        'client_secret' => env('RINGCENTRAL_CLIENT_SECRET'),
        'jwt' => env('RINGCENTRAL_JWT'),
        'account_id' => env('RINGCENTRAL_ACCOUNT_ID', '~'),
        // A call is credited to a phone click only when it starts within this
        // window after the click.
        'match_window_seconds' => (int) env('RINGCENTRAL_MATCH_WINDOW_SECONDS', 60),
        // How long we keep asking RingCentral about a click. It has to outlast the
        // match window, because a call shows up in the log only after it ends.
        'lookup_window_minutes' => (int) env('RINGCENTRAL_LOOKUP_WINDOW_MINUTES', 10),
        'clock_tolerance_seconds' => (int) env('RINGCENTRAL_CLOCK_TOLERANCE_SECONDS', 30),
        'retry_delay_seconds' => (int) env('RINGCENTRAL_RETRY_DELAY_SECONDS', 120),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => rtrim((string) env('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-mini-transcribe'),
        'summary_model' => env('OPENAI_SUMMARY_MODEL', 'gpt-5.4-nano'),
        'transcript_min_duration_seconds' => (int) env('OPENAI_TRANSCRIPT_MIN_DURATION_SECONDS', 20),
        'transcript_stuck_minutes' => (int) env('OPENAI_TRANSCRIPT_STUCK_MINUTES', 15),
    ],

    'sitemap' => [
        'base_url' => env('SITEMAP_BASE_URL', 'https://www.deluxewindows.com'),
        'excluded_paths' => [
            '/checkout',
            '/order-confirmation',
            '/paypal-checkout',
            '/search',
            '/product/1-property-credit',
            '/product/2-property-credit',
            '/product/5-property-credit',
            '/global-settings/default',
            '/windows/martin-elevate',
            '/windows/martin-vivid',
            '/windows/marvin-essne',
            '/windows/marvin-modern',
            '/windows/marvin-ultimate',
            '/windows/marvin-windows',
            '/window-styles/brand-simonton-single-hung-style-xtuvg',
            '/door-types/italwindows-steel-doors-j3z67',
        ],
    ],

];
