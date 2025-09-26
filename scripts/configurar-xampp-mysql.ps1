# Script para configurar XAMPP con MySQL y ejecutar migraciones
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "=== CONFIGURACIÓN XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host ""

# Verificar si XAMPP está instalado
$xamppPath = "C:\xampp"
if (-not (Test-Path $xamppPath)) {
    Write-Host "❌ XAMPP no encontrado en C:\xampp" -ForegroundColor Red
    Write-Host "Por favor instala XAMPP desde https://www.apachefriends.org/" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ XAMPP encontrado en: $xamppPath" -ForegroundColor Green

# Verificar si MySQL está ejecutándose
Write-Host ""
Write-Host "🔍 Verificando estado de MySQL..." -ForegroundColor Cyan

try {
    $mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($mysqlProcess) {
        Write-Host "✅ MySQL está ejecutándose (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    } else {
        Write-Host "⚠️  MySQL no está ejecutándose. Iniciando..." -ForegroundColor Yellow
        Start-Process -FilePath "$xamppPath\mysql\bin\mysqld.exe" -ArgumentList "--console" -WindowStyle Hidden
        Start-Sleep -Seconds 5
        Write-Host "✅ MySQL iniciado" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Error al verificar/iniciar MySQL: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Configurar archivo .env para XAMPP
Write-Host ""
Write-Host "🔧 Configurando archivo .env para XAMPP..." -ForegroundColor Cyan

$envContent = @"
# Configuración para desarrollo con XAMPP/MySQL
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
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

# Configuración de Correo
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@csdt.local"
MAIL_FROM_NAME="${APP_NAME}"

# Configuración de Vite
VITE_APP_NAME="${APP_NAME}"

# Configuración de OpenAI (opcional)
OPENAI_API_KEY=

# Configuración de LexisNexis AI (opcional)
LEXISNEXIS_API_URL=https://api.lexisnexis.com
LEXISNEXIS_API_KEY=

# Configuración de Servicio de IA (opcional)
IA_SERVICE_URL=http://127.0.0.1:8001
IA_SERVICE_TIMEOUT=120
IA_SERVICE_RETRY_ATTEMPTS=3

# Configuración de IA Profesional
IA_LEGAL_ESPECIALISTA="Dr. María Elena Rodríguez"
IA_TECNICA_ESPECIALISTA="Ing. Carlos Andrés Mendoza"
IA_INFORMATICA_ESPECIALISTA="Dr. Ana Lucía Herrera"
IA_VEEDURIA_ESPECIALISTA="Dr. Roberto Carlos Silva"

# Configuración de Sentry (opcional)
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1
"@

$envPath = ".\backend-csdt\.env"
$envContent | Out-File -FilePath $envPath -Encoding UTF8
Write-Host "✅ Archivo .env configurado" -ForegroundColor Green

# Crear base de datos si no existe
Write-Host ""
Write-Host "🗄️  Creando base de datos csdt_local..." -ForegroundColor Cyan

$createDbScript = @"
CREATE DATABASE IF NOT EXISTS csdt_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE csdt_local;
"@

$createDbScript | Out-File -FilePath ".\temp_create_db.sql" -Encoding UTF8

try {
    & "$xamppPath\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS csdt_local CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Write-Host "✅ Base de datos csdt_local creada/verificada" -ForegroundColor Green
} catch {
    Write-Host "❌ Error al crear base de datos: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Intenta crear la base de datos manualmente en phpMyAdmin" -ForegroundColor Yellow
}

# Limpiar archivo temporal
Remove-Item ".\temp_create_db.sql" -ErrorAction SilentlyContinue

# Ejecutar migraciones
Write-Host ""
Write-Host "🚀 Ejecutando migraciones..." -ForegroundColor Cyan

Set-Location ".\backend-csdt"

try {
    # Limpiar caché
    Write-Host "🧹 Limpiando caché..." -ForegroundColor Yellow
    & php artisan config:clear
    & php artisan cache:clear
    & php artisan route:clear
    & php artisan view:clear

    # Ejecutar migraciones
    Write-Host "📊 Ejecutando migraciones..." -ForegroundColor Yellow
    & php artisan migrate --force

    Write-Host "✅ Migraciones ejecutadas correctamente" -ForegroundColor Green
} catch {
    Write-Host "❌ Error al ejecutar migraciones: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Verifica que la base de datos esté configurada correctamente" -ForegroundColor Yellow
}

# Verificar tablas creadas
Write-Host ""
Write-Host "🔍 Verificando tablas creadas..." -ForegroundColor Cyan

try {
    $tables = & "$xamppPath\mysql\bin\mysql.exe" -u root -D csdt_local -e "SHOW TABLES;" 2>$null
    if ($tables) {
        Write-Host "✅ Tablas encontradas:" -ForegroundColor Green
        $tables | ForEach-Object { Write-Host "  - $_" -ForegroundColor White }
    } else {
        Write-Host "⚠️  No se encontraron tablas" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar tablas: $($_.Exception.Message)" -ForegroundColor Red
}

# Generar clave de aplicación si es necesario
Write-Host ""
Write-Host "🔑 Verificando clave de aplicación..." -ForegroundColor Cyan

try {
    & php artisan key:generate --force
    Write-Host "✅ Clave de aplicación generada" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Error al generar clave: $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== CONFIGURACIÓN COMPLETADA ===" -ForegroundColor Green
Write-Host "Base de datos: csdt_local" -ForegroundColor White
Write-Host "Host: 127.0.0.1:3306" -ForegroundColor White
Write-Host "Usuario: root" -ForegroundColor White
Write-Host "Contraseña: (vacía)" -ForegroundColor White
Write-Host ""
Write-Host "Para iniciar el servidor:" -ForegroundColor Cyan
Write-Host "  cd backend-csdt" -ForegroundColor White
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "Para acceder a phpMyAdmin:" -ForegroundColor Cyan
Write-Host "  http://localhost/phpmyadmin" -ForegroundColor White
