# Script completo para migrar a XAMPP MySQL
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "=== MIGRACIÓN COMPLETA XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host "Este script configurará y migrará el sistema a XAMPP MySQL" -ForegroundColor Cyan
Write-Host ""

# Verificar si estamos en el directorio correcto
if (-not (Test-Path "artisan")) {
    Write-Host "❌ No se encontró el archivo artisan" -ForegroundColor Red
    Write-Host "Ejecuta este script desde el directorio backend-csdt" -ForegroundColor Yellow
    exit 1
}

Write-Host "✅ Directorio correcto detectado" -ForegroundColor Green

# Paso 1: Verificar sistema
Write-Host ""
Write-Host "🔍 PASO 1: Verificando sistema..." -ForegroundColor Cyan
& .\scripts\verificar-xampp-mysql.ps1

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ Verificación falló. Corrige los errores antes de continuar." -ForegroundColor Red
    exit 1
}

# Paso 2: Configurar XAMPP
Write-Host ""
Write-Host "🔧 PASO 2: Configurando XAMPP..." -ForegroundColor Cyan
& .\scripts\configurar-xampp-mysql.ps1

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ Configuración falló. Revisa los errores." -ForegroundColor Red
    exit 1
}

# Paso 3: Ejecutar migraciones
Write-Host ""
Write-Host "🚀 PASO 3: Ejecutando migraciones..." -ForegroundColor Cyan
& .\scripts\ejecutar-migraciones-xampp.ps1

if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "❌ Migraciones fallaron. Revisa los errores." -ForegroundColor Red
    exit 1
}

# Paso 4: Verificación final
Write-Host ""
Write-Host "✅ PASO 4: Verificación final..." -ForegroundColor Cyan

# Verificar que las tablas principales existen
$tablasEsperadas = @("usu", "rol", "usu_rol", "perm", "rol_perm", "vee", "don", "tar", "arc", "cfg", "log", "ai_ana", "ai_nar")

Write-Host "Verificando tablas principales..." -ForegroundColor Yellow

try {
    $tables = & php artisan tinker --execute="echo implode(',', array_column(DB::select('SHOW TABLES'), 'Tables_in_csdt_local'));"
    $tablasExistentes = $tables -split ","
    
    $tablasFaltantes = @()
    foreach ($tabla in $tablasEsperadas) {
        if ($tablasExistentes -notcontains $tabla) {
            $tablasFaltantes += $tabla
        }
    }
    
    if ($tablasFaltantes.Count -eq 0) {
        Write-Host "✅ Todas las tablas principales están presentes" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Faltan algunas tablas: $($tablasFaltantes -join ', ')" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar tablas: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar conexión de la aplicación
Write-Host ""
Write-Host "Verificando conexión de la aplicación..." -ForegroundColor Yellow

try {
    & php artisan tinker --execute="echo 'Conexión: ' . (DB::connection()->getDatabaseName());"
    Write-Host "✅ Aplicación conectada correctamente" -ForegroundColor Green
} catch {
    Write-Host "❌ Error de conexión en la aplicación: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== MIGRACIÓN COMPLETADA EXITOSAMENTE ===" -ForegroundColor Green
Write-Host ""
Write-Host "🎉 El sistema CSDT está listo para usar con XAMPP MySQL" -ForegroundColor Cyan
Write-Host ""
Write-Host "Información de la base de datos:" -ForegroundColor White
Write-Host "  • Host: 127.0.0.1:3306" -ForegroundColor White
Write-Host "  • Base de datos: csdt_local" -ForegroundColor White
Write-Host "  • Usuario: root" -ForegroundColor White
Write-Host "  • Contraseña: (vacía)" -ForegroundColor White
Write-Host ""
Write-Host "Para iniciar el servidor:" -ForegroundColor Cyan
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host ""
Write-Host "Para acceder a phpMyAdmin:" -ForegroundColor Cyan
Write-Host "  http://localhost/phpmyadmin" -ForegroundColor White
Write-Host ""
Write-Host "Para verificar el estado:" -ForegroundColor Cyan
Write-Host "  .\scripts\verificar-xampp-mysql.ps1" -ForegroundColor White
