# Script para configurar XAMPP con MySQL y ejecutar migraciones
# CONSEJO SOCIAL DE VEEDURIA Y DESARROLLO TERRITORIAL

Write-Host "=== CONFIGURACION XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host ""

# Verificar si XAMPP esta instalado
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Host "ERROR - XAMPP no encontrado en C:\xampp" -ForegroundColor Red
    Write-Host "Por favor instala XAMPP desde https://www.apachefriends.org/" -ForegroundColor Yellow
    exit 1
}

Write-Host "OK - XAMPP encontrado en: $xamppPath" -ForegroundColor Green

# Verificar si MySQL esta ejecutandose
Write-Host ""
Write-Host "Verificando estado de MySQL..." -ForegroundColor Cyan

try {
    $mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($mysqlProcess) {
        Write-Host "OK - MySQL esta ejecutandose (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    } else {
        Write-Host "ADVERTENCIA - MySQL no esta ejecutandose. Iniciando..." -ForegroundColor Yellow
        Start-Process -FilePath "$xamppPath\mysql\bin\mysqld.exe" -ArgumentList "--console" -WindowStyle Hidden
        Start-Sleep -Seconds 5
        Write-Host "OK - MySQL iniciado" -ForegroundColor Green
    }
} catch {
    Write-Host "ERROR - Error al verificar/iniciar MySQL: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Configurar archivo .env para XAMPP
Write-Host ""
Write-Host "Configurando archivo .env para XAMPP..." -ForegroundColor Cyan

$envContent = @"
# Configuracion para desarrollo con XAMPP/MySQL
APP_NAME="CONSEJO SOCIAL DE VEEDURIA Y DESARROLLO TERRITORIAL"
APP_ENV=local
APP_KEY=base64:MADwRFrmHcoT+Zb9VlbzdvRhQdK9F5t6uS+DPk24sZM=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://127.0.0.1:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=es_ES

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Base de datos MySQL para desarrollo completo
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_local
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Configuracion de Correo
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@csdt.local"
MAIL_FROM_NAME="${APP_NAME}"

# Configuracion de Vite
VITE_APP_NAME="${APP_NAME}"

# Configuracion de OpenAI (opcional)
OPENAI_API_KEY=

# Configuracion de LexisNexis AI (opcional)
LEXISNEXIS_API_URL=https://api.lexisnexis.com
LEXISNEXIS_API_KEY=

# Configuracion de Servicio de IA (opcional)
IA_SERVICE_URL=http://127.0.0.1:8001
IA_SERVICE_TIMEOUT=120
IA_SERVICE_RETRY_ATTEMPTS=3

# Configuracion de IA Profesional
IA_LEGAL_ESPECIALISTA="Dr. Maria Elena Rodriguez"
IA_TECNICA_ESPECIALISTA="Ing. Carlos Andres Mendoza"
IA_INFORMATICA_ESPECIALISTA="Dr. Ana Lucia Herrera"
IA_VEEDURIA_ESPECIALISTA="Dr. Roberto Carlos Silva"

# Configuracion de Sentry (opcional)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
"@

$envPath = ".\.env"
$envContent | Out-File -FilePath $envPath -Encoding UTF8
Write-Host "OK - Archivo .env configurado" -ForegroundColor Green

# Crear base de datos si no existe
Write-Host ""
Write-Host "Creando base de datos csdt_local..." -ForegroundColor Cyan

try {
    $mysqlExe = "$xamppPath\mysql\bin\mysql.exe"
    & $mysqlExe -u root -e "CREATE DATABASE IF NOT EXISTS csdt_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Write-Host "OK - Base de datos csdt_local creada/verificada" -ForegroundColor Green
} catch {
    Write-Host "ERROR - Error al crear base de datos: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Intenta crear la base de datos manualmente en phpMyAdmin" -ForegroundColor Yellow
}

# Ejecutar migraciones
Write-Host ""
Write-Host "Ejecutando migraciones..." -ForegroundColor Cyan

try {
    # Limpiar cache
    Write-Host "Limpiando cache..." -ForegroundColor Yellow
    & php artisan config:clear
    & php artisan cache:clear
    & php artisan route:clear
    & php artisan view:clear

    # Ejecutar migraciones
    Write-Host "Ejecutando migraciones..." -ForegroundColor Yellow
    & php artisan migrate --force

    Write-Host "OK - Migraciones ejecutadas correctamente" -ForegroundColor Green
} catch {
    Write-Host "ERROR - Error al ejecutar migraciones: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Verifica que la base de datos este configurada correctamente" -ForegroundColor Yellow
}

# Verificar tablas creadas
Write-Host ""
Write-Host "Verificando tablas creadas..." -ForegroundColor Cyan

try {
    $mysqlExe = "$xamppPath\mysql\bin\mysql.exe"
    $tables = & $mysqlExe -u root -D csdt_local -e "SHOW TABLES;" 2>$null
    if ($tables) {
        Write-Host "OK - Tablas encontradas:" -ForegroundColor Green
        $tables | ForEach-Object { 
            if ($_ -notmatch "Tables_in_csdt_local") {
                Write-Host "  - $_" -ForegroundColor White 
            }
        }
    } else {
        Write-Host "ADVERTENCIA - No se encontraron tablas" -ForegroundColor Yellow
    }
} catch {
    Write-Host "ERROR - Error al verificar tablas: $($_.Exception.Message)" -ForegroundColor Red
}

# Generar clave de aplicacion si es necesario
Write-Host ""
Write-Host "Verificando clave de aplicacion..." -ForegroundColor Cyan

try {
    & php artisan key:generate --force
    Write-Host "OK - Clave de aplicacion generada" -ForegroundColor Green
} catch {
    Write-Host "ADVERTENCIA - Error al generar clave: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== CONFIGURACION COMPLETADA ===" -ForegroundColor Green
Write-Host "Base de datos: csdt_local" -ForegroundColor White
Write-Host "Host: 127.0.0.1:3306" -ForegroundColor White
Write-Host "Usuario: root" -ForegroundColor White
Write-Host "Contrasena: (vacia)" -ForegroundColor White
Write-Host ""
Write-Host "Para iniciar el servidor:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "Para acceder a phpMyAdmin:" -ForegroundColor Cyan
Write-Host "  http://localhost/phpmyadmin" -ForegroundColor White
