<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EncriptarDatosSensibles;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\MonitorRendimiento;
use App\Http\Middleware\RateLimitingAvanzado;
use App\Http\Middleware\SanitizarInputs;
use App\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Configurar CORS para permitir peticiones desde el frontend
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Registrar middleware personalizado
        $middleware->alias([
            'verificar.rol' => VerificarRol::class,
            'monitor.rendimiento' => MonitorRendimiento::class,
            'csp' => ContentSecurityPolicy::class,
            'sanitizar.inputs' => SanitizarInputs::class,
            'rate.limit' => RateLimitingAvanzado::class,
            'encriptar.datos' => EncriptarDatosSensibles::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            MonitorRendimiento::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
