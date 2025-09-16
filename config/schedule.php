<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CSDT IA Auto-Improvement Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de tareas programadas para mejoras automáticas del sistema de IA
    | del CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
    |
    */

    'enabled' => env('IA_AUTO_IMPROVEMENT_ENABLED', true),

    'tasks' => [
        /*
        |--------------------------------------------------------------------------
        | Verificación de Actualizaciones de IA
        |--------------------------------------------------------------------------
        |
        | Verifica automáticamente nuevas versiones de modelos y proveedores de IA
        | Frecuencia: Cada 6 horas
        |
        */
        'verificar_actualizaciones_ia' => [
            'command' => 'ia:mejorar-automaticamente --tipo=modelos',
            'schedule' => 'everySixHours',
            'enabled' => true,
            'description' => 'Verificar actualizaciones de modelos de IA disponibles'
        ],

        /*
        |--------------------------------------------------------------------------
        | Alimentación Automática de Librerías
        |--------------------------------------------------------------------------
        |
        | Actualiza automáticamente las librerías de IA cuando hay nuevas versiones
        | Frecuencia: Diariamente a las 2:00 AM
        |
        */
        'alimentar_librerias' => [
            'command' => 'ia:mejorar-automaticamente --tipo=librerias',
            'schedule' => 'dailyAt',
            'time' => '02:00',
            'enabled' => true,
            'description' => 'Actualizar librerías de IA automáticamente'
        ],

        /*
        |--------------------------------------------------------------------------
        | Optimización de Configuración
        |--------------------------------------------------------------------------
        |
        | Optimiza automáticamente la configuración del sistema basada en métricas
        | Frecuencia: Semanalmente los domingos a las 3:00 AM
        |
        */
        'optimizar_configuracion' => [
            'command' => 'ia:mejorar-automaticamente --tipo=configuracion',
            'schedule' => 'weeklyOn',
            'day' => 0, // Domingo
            'time' => '03:00',
            'enabled' => true,
            'description' => 'Optimizar configuración del sistema de IA'
        ],

        /*
        |--------------------------------------------------------------------------
        | Limpieza de Cachés
        |--------------------------------------------------------------------------
        |
        | Limpia cachés obsoletos y libera espacio
        | Frecuencia: Diariamente a las 4:00 AM
        |
        */
        'limpiar_cache' => [
            'command' => 'ia:mejorar-automaticamente --tipo=cache',
            'schedule' => 'dailyAt',
            'time' => '04:00',
            'enabled' => true,
            'description' => 'Limpiar cachés obsoletos del sistema de IA'
        ],

        /*
        |--------------------------------------------------------------------------
        | Verificación de Salud del Sistema
        |--------------------------------------------------------------------------
        |
        | Verifica el estado general del sistema de IA y genera recomendaciones
        | Frecuencia: Cada hora
        |
        */
        'verificar_salud' => [
            'command' => 'ia:mejorar-automaticamente --tipo=salud',
            'schedule' => 'hourly',
            'enabled' => true,
            'description' => 'Verificar salud del sistema de IA'
        ],

        /*
        |--------------------------------------------------------------------------
        | Mejora Completa del Sistema
        |--------------------------------------------------------------------------
        |
        | Ejecuta todas las mejoras automáticamente
        | Frecuencia: Semanalmente los sábados a las 1:00 AM
        |
        */
        'mejora_completa' => [
            'command' => 'ia:mejorar-automaticamente',
            'schedule' => 'weeklyOn',
            'day' => 6, // Sábado
            'time' => '01:00',
            'enabled' => true,
            'description' => 'Ejecutar todas las mejoras del sistema de IA'
        ],

        /*
        |--------------------------------------------------------------------------
        | Backup de Configuraciones
        |--------------------------------------------------------------------------
        |
        | Crea backup de configuraciones críticas antes de aplicar cambios
        | Frecuencia: Antes de cada mejora automática
        |
        */
        'backup_configuraciones' => [
            'command' => 'config:cache && config:clear',
            'schedule' => 'before',
            'target_commands' => ['ia:mejorar-automaticamente'],
            'enabled' => true,
            'description' => 'Crear backup de configuraciones antes de mejoras'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Notificaciones
    |--------------------------------------------------------------------------
    |
    | Configuración para notificar sobre eventos importantes del sistema de IA
    |
    */
    'notifications' => [
        'enabled' => env('IA_NOTIFICATIONS_ENABLED', true),
        'channels' => [
            'email' => env('IA_NOTIFICATION_EMAIL', true),
            'slack' => env('IA_NOTIFICATION_SLACK', false),
            'database' => env('IA_NOTIFICATION_DATABASE', true),
        ],
        'events' => [
            'actualizacion_exitosa' => true,
            'error_critico' => true,
            'mejora_aplicada' => true,
            'libreria_actualizada' => true,
            'salud_degradada' => true,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Límites y Restricciones
    |--------------------------------------------------------------------------
    |
    | Configuración de límites para evitar sobrecarga del sistema
    |
    */
    'limits' => [
        'max_concurrent_tasks' => env('IA_MAX_CONCURRENT_TASKS', 3),
        'max_execution_time' => env('IA_MAX_EXECUTION_TIME', 300), // 5 minutos
        'rate_limit_per_hour' => env('IA_RATE_LIMIT_PER_HOUR', 10),
        'max_retries' => env('IA_MAX_RETRIES', 3),
        'retry_delay' => env('IA_RETRY_DELAY', 60), // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Monitoreo
    |--------------------------------------------------------------------------
    |
    | Configuración para monitorear el rendimiento de las mejoras automáticas
    |
    */
    'monitoring' => [
        'enabled' => env('IA_MONITORING_ENABLED', true),
        'metrics' => [
            'execution_time' => true,
            'success_rate' => true,
            'error_rate' => true,
            'resource_usage' => true,
            'update_frequency' => true,
        ],
        'alerts' => [
            'execution_timeout' => env('IA_ALERT_EXECUTION_TIMEOUT', 600), // 10 minutos
            'error_threshold' => env('IA_ALERT_ERROR_THRESHOLD', 5), // 5 errores por hora
            'no_updates_period' => env('IA_ALERT_NO_UPDATES_PERIOD', 168), // 7 días
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Recuperación
    |--------------------------------------------------------------------------
    |
    | Configuración para recuperación automática en caso de fallos
    |
    */
    'recovery' => [
        'enabled' => env('IA_RECOVERY_ENABLED', true),
        'strategies' => [
            'rollback_on_failure' => true,
            'circuit_breaker_reset' => true,
            'cache_invalidation' => true,
            'service_restart' => false,
        ],
        'rollback_versions' => env('IA_ROLLBACK_VERSIONS', 5), // Mantener 5 versiones previas
    ]
];
