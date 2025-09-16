<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Producción - CSDT
    |--------------------------------------------------------------------------
    |
    | Este archivo contiene la configuración recomendada para el entorno
    | de producción del CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
    |
    */

    'app' => [
        'name' => 'CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL',
        'env' => 'production',
        'debug' => false,
        'url' => 'https://csdt.org',
        'timezone' => 'America/Bogota',
        'locale' => 'es',
        'fallback_locale' => 'es',
        'faker_locale' => 'es_CO',
    ],

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'csdt_production'),
        'username' => env('DB_USERNAME', 'csdt_user'),
        'password' => env('DB_PASSWORD', ''),
    ],

    'cache' => [
        'store' => env('CACHE_STORE', 'redis'),
        'prefix' => 'csdt_cache',
    ],

    'session' => [
        'driver' => env('SESSION_DRIVER', 'redis'),
        'lifetime' => 120,
        'encrypt' => false,
        'path' => '/',
        'domain' => '.csdt.org',
        'secure' => true,
    ],

    'mail' => [
        'mailer' => env('MAIL_MAILER', 'smtp'),
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', '587'),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@csdt.org'),
        'from_name' => env('MAIL_FROM_NAME', 'CSDT'),
    ],

    'cors' => [
        'allowed_origins' => ['https://csdt.org', 'https://www.csdt.org'],
        'allowed_headers' => ['*'],
        'allowed_methods' => ['*'],
        'allow_credentials' => true,
        'max_age' => 86400,
    ],

    'sanctum' => [
        'stateful_domains' => ['csdt.org', 'www.csdt.org'],
        'guard' => 'web',
        'expiry' => 525600, // 1 año en minutos
    ],

    'logging' => [
        'channel' => 'stack',
        'level' => 'error',
        'deprecations_channel' => null,
    ],

    'filesystems' => [
        'disk' => 'public',
    ],

    'queue' => [
        'connection' => 'database',
    ],

    'broadcast' => [
        'connection' => 'log',
        'driver' => 'log',
    ],
];
