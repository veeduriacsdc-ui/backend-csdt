# Script para verificar la configuración de XAMPP MySQL
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "=== VERIFICACIÓN XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host ""

# Verificar XAMPP
Write-Host "1. Verificando XAMPP..." -ForegroundColor Cyan
$xamppPath = "C:\xampp"
if (Test-Path $xamppPath) {
    Write-Host "✅ XAMPP encontrado en: $xamppPath" -ForegroundColor Green
} else {
    Write-Host "❌ XAMPP no encontrado en C:\xampp" -ForegroundColor Red
    Write-Host "Instala XAMPP desde: https://www.apachefriends.org/" -ForegroundColor Yellow
}

# Verificar MySQL
Write-Host ""
Write-Host "2. Verificando MySQL..." -ForegroundColor Cyan
try {
    $mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($mysqlProcess) {
        Write-Host "✅ MySQL ejecutándose (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    } else {
        Write-Host "⚠️  MySQL no está ejecutándose" -ForegroundColor Yellow
        Write-Host "Inicia MySQL desde el panel de control de XAMPP" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar MySQL" -ForegroundColor Red
}

# Verificar conexión MySQL
Write-Host ""
Write-Host "3. Verificando conexión MySQL..." -ForegroundColor Cyan
try {
    $result = & "C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT VERSION();" 2>$null
    if ($result) {
        Write-Host "✅ Conexión MySQL exitosa" -ForegroundColor Green
        Write-Host "Versión: $($result[1])" -ForegroundColor White
    } else {
        Write-Host "❌ No se pudo conectar a MySQL" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Error de conexión MySQL: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar base de datos
Write-Host ""
Write-Host "4. Verificando base de datos csdt_local..." -ForegroundColor Cyan
try {
    $dbExists = & "C:\xampp\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES LIKE 'csdt_local';" 2>$null
    if ($dbExists -match "csdt_local") {
        Write-Host "✅ Base de datos csdt_local existe" -ForegroundColor Green
        
        # Verificar tablas
        $tables = & "C:\xampp\mysql\bin\mysql.exe" -u root -D csdt_local -e "SHOW TABLES;" 2>$null
        if ($tables) {
            $tableCount = ($tables | Measure-Object).Count
            Write-Host "✅ Tablas encontradas: $tableCount" -ForegroundColor Green
            $tables | ForEach-Object { 
                if ($_ -notmatch "Tables_in_csdt_local") {
                    Write-Host "  - $_" -ForegroundColor White 
                }
            }
        } else {
            Write-Host "⚠️  No se encontraron tablas en la base de datos" -ForegroundColor Yellow
        }
    } else {
        Write-Host "⚠️  Base de datos csdt_local no existe" -ForegroundColor Yellow
        Write-Host "Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar base de datos: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar archivo .env
Write-Host ""
Write-Host "5. Verificando archivo .env..." -ForegroundColor Cyan
if (Test-Path ".env") {
    Write-Host "✅ Archivo .env existe" -ForegroundColor Green
    
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "DB_CONNECTION=mysql") {
        Write-Host "✅ Configuración MySQL en .env" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Configuración MySQL no encontrada en .env" -ForegroundColor Yellow
    }
    
    if ($envContent -match "DB_DATABASE=csdt_local") {
        Write-Host "✅ Base de datos configurada como csdt_local" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Base de datos no configurada como csdt_local" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ Archivo .env no existe" -ForegroundColor Red
    Write-Host "Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor Yellow
}

# Verificar PHP
Write-Host ""
Write-Host "6. Verificando PHP..." -ForegroundColor Cyan
try {
    $phpVersion = & php -v 2>$null
    if ($phpVersion) {
        Write-Host "✅ PHP disponible" -ForegroundColor Green
        $version = ($phpVersion[0] -split " ")[1]
        Write-Host "Versión: $version" -ForegroundColor White
    } else {
        Write-Host "❌ PHP no disponible" -ForegroundColor Red
        Write-Host "Asegúrate de que PHP esté en el PATH" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar PHP: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar Composer
Write-Host ""
Write-Host "7. Verificando Composer..." -ForegroundColor Cyan
try {
    $composerVersion = & composer --version 2>$null
    if ($composerVersion) {
        Write-Host "✅ Composer disponible" -ForegroundColor Green
        Write-Host "Versión: $($composerVersion[0])" -ForegroundColor White
    } else {
        Write-Host "❌ Composer no disponible" -ForegroundColor Red
        Write-Host "Instala Composer desde: https://getcomposer.org/" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ Error al verificar Composer: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== VERIFICACIÓN COMPLETADA ===" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos pasos:" -ForegroundColor Cyan
Write-Host "1. Si hay errores, corrígelos antes de continuar" -ForegroundColor White
Write-Host "2. Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor White
Write-Host "3. Luego: .\scripts\ejecutar-migraciones-xampp.ps1" -ForegroundColor White
Write-Host "4. Finalmente: php artisan serve" -ForegroundColor White
