# Script simple para ejecutar migraciones en XAMPP MySQL
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "=== EJECUTAR MIGRACIONES XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host ""

# Verificar si estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "❌ No se encontró el archivo artisan" -ForegroundColor Red
    Write-Host "Ejecuta este script desde el directorio backend-csdt" -ForegroundColor Yellow
    exit 1
}

# Verificar si existe el archivo .env
if (-not (Test-Path ".env")) {
    Write-Host "❌ No se encontró el archivo .env" -ForegroundColor Red
    Write-Host "Ejecuta primero: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Archivos de configuración encontrados" -ForegroundColor Green

# Limpiar caché
Write-Host ""
Write-Host "🧹 Limpiando caché..." -ForegroundColor Cyan
try {
    & php artisan config:clear
    & php artisan cache:clear
    & php artisan route:clear
    & php artisan view:clear
    Write-Host "✅ Caché limpiado" -ForegroundColor Green
} catch {
    Write-Host "⚠️  Error al limpiar caché: $($_.Exception.Message)" -ForegroundColor Yellow
}

# Verificar conexión a la base de datos
Write-Host ""
Write-Host "🔍 Verificando conexión a la base de datos..." -ForegroundColor Cyan
try {
    & php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión exitosa';"
    Write-Host "✅ Conexión a la base de datos exitosa" -ForegroundColor Green
} catch {
    Write-Host "❌ Error de conexión a la base de datos" -ForegroundColor Red
    Write-Host "Verifica que MySQL esté ejecutándose y la base de datos csdt_local exista" -ForegroundColor Yellow
    exit 1
}

# Ejecutar migraciones
Write-Host ""
Write-Host "🚀 Ejecutando migraciones..." -ForegroundColor Cyan
try {
    & php artisan migrate --force
    Write-Host "✅ Migraciones ejecutadas correctamente" -ForegroundColor Green
} catch {
    Write-Host "❌ Error al ejecutar migraciones: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host "Verifica los logs para más detalles" -ForegroundColor Yellow
    exit 1
}

# Verificar tablas creadas
Write-Host ""
Write-Host "🔍 Verificando tablas creadas..." -ForegroundColor Cyan
try {
    $tables = & php artisan tinker --execute="echo implode(', ', DB::select('SHOW TABLES'));"
    if ($tables) {
        Write-Host "✅ Tablas encontradas: $tables" -ForegroundColor Green
    } else {
        Write-Host "⚠️  No se encontraron tablas" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar tablas: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== MIGRACIONES COMPLETADAS ===" -ForegroundColor Green
Write-Host "El sistema está listo para usar" -ForegroundColor White
