<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nombre de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Este valor es el nombre de tu aplicación, que será utilizado cuando el
    | framework necesite colocar el nombre de la aplicación en una notificación o
    | otros elementos de interfaz donde se necesite mostrar el nombre de la aplicación.
    |
    */

    'name' => env('APP_NAME', 'CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL'),

    /*
    |--------------------------------------------------------------------------
    | Entorno de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Este valor determina el "entorno" en el que tu aplicación está ejecutándose
    | actualmente. Esto puede determinar cómo prefieres configurar varios
    | servicios que la aplicación utiliza. Establece esto en tu archivo ".env".
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Modo Debug de la Aplicación
    |--------------------------------------------------------------------------
    |
    | Cuando tu aplicación está en modo debug, se mostrarán mensajes de error detallados con
    | trazas de pila en cada error que ocurra dentro de tu aplicación. Si está deshabilitado,
    | se muestra una página de error genérica simple.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
