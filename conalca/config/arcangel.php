<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Arcangel API Mode
    |--------------------------------------------------------------------------
    |
    | This value determines which environment to use for Arcangel API calls.
    | Supported: "production", "development"
    |
    */

    'mode' => env('ARCANGEL_MODE', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Arcangel API Base URLs
    |--------------------------------------------------------------------------
    |
    | Base URLs for production and development environments
    |
    */

    'base_url' => env('ARCANGEL_BASE_URL', 'https://arcangel.conalca.com.co/api/'),
    'base_url_dev' => env('ARCANGEL_BASE_URL_DEV', 'https://dev.arcangel.conalca.com.co/api/'),

    /*
    |--------------------------------------------------------------------------
    | Arcangel API Keys
    |--------------------------------------------------------------------------
    |
    | API Keys for production and development environments
    |
    */

    'api_key' => env('ARCANGEL_API_KEY'),
    'api_key_dev' => env('ARCANGEL_API_KEY_DEV'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout for API requests in seconds
    |
    */

    'timeout' => env('ARCANGEL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of retries and delay between retries (in milliseconds)
    |
    */

    'retry_times' => env('ARCANGEL_RETRY_TIMES', 3),
    'retry_delay' => env('ARCANGEL_RETRY_DELAY', 100),

];
