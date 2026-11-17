<?php

return [
    'name' => env('APP_NAME', 'Pisica Ta'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Europe/Bucharest',
    'locale' => 'ro',
    'fallback_locale' => 'en',
    'faker_locale' => 'ro_RO',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => 'file'],
];
