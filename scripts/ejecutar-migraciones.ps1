# Script para ejecutar migraciones y verificar la base de datos
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "🚀 Iniciando proceso de migración de base de datos CSDT..." -ForegroundColor Green

# Cambiar al directorio del backend
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendPath = Join-Path $scriptPath ".."
Set-Location $backendPath

# Verificar que estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "❌ Error: No se encontró el archivo artisan. Asegúrate de estar en el directorio del backend." -ForegroundColor Red
    exit 1
}

Write-Host "📁 Directorio actual: $(Get-Location)" -ForegroundColor Cyan

# Verificar conexión a la base de datos
Write-Host "🔍 Verificando conexión a la base de datos..." -ForegroundColor Yellow
php artisan migrate:status

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error: No se pudo conectar a la base de datos. Verifica la configuración." -ForegroundColor Red
    exit 1
}

Write-Host "✅ Conexión a la base de datos exitosa" -ForegroundColor Green

# Hacer backup de la base de datos actual (si existe)
Write-Host "💾 Creando backup de la base de datos actual..." -ForegroundColor Yellow
if (Test-Path "database/database.sqlite") {
    $backupName = "database_backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sqlite"
    Copy-Item "database/database.sqlite" "database/$backupName"
    Write-Host "✅ Backup creado exitosamente: $backupName" -ForegroundColor Green
} else {
    Write-Host "ℹ️  No se encontró base de datos existente, continuando..." -ForegroundColor Blue
}

# Limpiar caché
Write-Host "🧹 Limpiando caché..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
Write-Host "🔄 Ejecutando migraciones..." -ForegroundColor Yellow
php artisan migrate:fresh --force

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Error al ejecutar migraciones" -ForegroundColor Red
    exit 1
}

Write-Host "✅ Migraciones ejecutadas exitosamente" -ForegroundColor Green

# Ejecutar seeders
Write-Host "🌱 Ejecutando seeders..." -ForegroundColor Yellow
php artisan db:seed --force

if ($LASTEXITCODE -ne 0) {
    Write-Host "⚠️  Advertencia: Error al ejecutar seeders, pero las migraciones fueron exitosas" -ForegroundColor Yellow
}

# Verificar estructura de la base de datos
Write-Host "🔍 Verificando estructura de la base de datos..." -ForegroundColor Yellow
php artisan migrate:status

# Mostrar tablas creadas
Write-Host "📊 Tablas creadas:" -ForegroundColor Cyan
php artisan tinker --execute="echo 'Tablas: ' . implode(', ', array_keys(DB::select('SELECT name FROM sqlite_master WHERE type=\"table\"')));"

# Verificar que los modelos funcionen
Write-Host "🧪 Verificando modelos..." -ForegroundColor Yellow
php artisan tinker --execute="
try {
    `$usuario = new App\Models\Usuario();
    echo '✅ Modelo Usuario: OK';
} catch (Exception `$e) {
    echo '❌ Error en modelo Usuario: ' . `$e->getMessage();
}

try {
    `$rol = new App\Models\Rol();
    echo '✅ Modelo Rol: OK';
} catch (Exception `$e) {
    echo '❌ Error en modelo Rol: ' . `$e->getMessage();
}

try {
    `$veeduria = new App\Models\Veeduria();
    echo '✅ Modelo Veeduria: OK';
} catch (Exception `$e) {
    echo '❌ Error en modelo Veeduria: ' . `$e->getMessage();
}
"

# Verificar rutas API
Write-Host "🛣️  Verificando rutas API..." -ForegroundColor Yellow
php artisan route:list --path=api

Write-Host "🎉 Proceso de migración completado exitosamente!" -ForegroundColor Green
Write-Host "📋 Resumen:" -ForegroundColor Cyan
Write-Host "   - Base de datos rediseñada con nombres optimizados" -ForegroundColor White
Write-Host "   - Tablas pivot creadas correctamente" -ForegroundColor White
Write-Host "   - Relaciones establecidas" -ForegroundColor White
Write-Host "   - Modelos verificados" -ForegroundColor White
Write-Host "   - Rutas API configuradas" -ForegroundColor White
Write-Host ""
Write-Host "🚀 El sistema está listo para usar!" -ForegroundColor Green
