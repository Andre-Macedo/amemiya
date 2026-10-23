<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://localhost:8081',
        'http://localhost:8082',
        'http://localhost:19006',
        'http://127.0.0.1:8081',
        'http://127.0.0.1:8082',
        'http://amemiya.localhost:3000',
        'http://amemiya.localhost:3001',
        'https://leantech.andremacedo.dev.br',
        'https://*.leantech.andremacedo.dev.br',
        'https://*.andremacedo.dev.br',
    ],

    'allowed_origins_patterns' => [
        '/^http:\/\/localhost(:\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(:\d+)?$/',
        '/^http:\/\/.*\.localhost(:\d+)?$/',
        '/^https:\/\/.*\.andremacedo\.dev\.br$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
