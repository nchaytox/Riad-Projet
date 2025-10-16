<?php

return [
    'csp' => env('CSP_DIRECTIVES', "default-src 'self'; img-src 'self' data:; font-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'; connect-src 'self'"),
    'referrer_policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    'permissions_policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=(), payment=()'),
    'enable_hsts_environments' => ['staging', 'production'],
    'hsts_max_age' => (int) env('HSTS_MAX_AGE', 63072000),
    'rate_limits' => [
        'auth' => [
            'attempts' => (int) env('RATE_LIMIT_AUTH_ATTEMPTS', 5),
            'decay' => (int) env('RATE_LIMIT_AUTH_DECAY', 1),
        ],
        'booking' => [
            'attempts' => (int) env('RATE_LIMIT_BOOKING_ATTEMPTS', 20),
            'decay' => (int) env('RATE_LIMIT_BOOKING_DECAY', 1),
        ],
    ],
];
