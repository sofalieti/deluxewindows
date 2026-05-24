<?php

return [
    'api_base_url' => env('WEBFLOW_API_BASE_URL', 'https://api.webflow.com/v2'),
    'api_token' => env('WEBFLOW_API_TOKEN'),
    'site_id' => env('WEBFLOW_SITE_ID'),
    'export_disk' => env('WEBFLOW_EXPORT_DISK', 'webflow_repo'),
    'export_root' => env('WEBFLOW_EXPORT_ROOT', 'current'),
];
