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

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => (static function () {
        $origins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

        if (app()->environment('production')) {
            $origins = array_values(array_filter($origins, fn ($origin) => $origin !== '*'));
        }

        if (empty($origins)) {
            if (app()->environment(['local', 'testing'])) {
                return [
                    'http://localhost:8000',
                    'http://127.0.0.1:8000',
                    'http://localhost:5173',
                    'http://127.0.0.1:5173',
                ];
            }

            return [];
        }

        return $origins;
    })(),

    'allowed_origins_patterns' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGIN_PATTERNS', '')))),

    'allowed_headers' => array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_HEADERS', '*')))),

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
