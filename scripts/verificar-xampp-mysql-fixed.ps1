# Script para verificar la configuración de XAMPP MySQL
# CONSEJO SOCIAL DE VEEDURIA Y DESARROLLO TERRITORIAL

Write-Host "=== VERIFICACION XAMPP MYSQL - CSDT ===" -ForegroundColor Green
Write-Host ""

# Verificar XAMPP
Write-Host "1. Verificando XAMPP..." -ForegroundColor Cyan
$xamppPath = "C:\xampp"
if (Test-Path $xamppPath) {
    Write-Host "OK - XAMPP encontrado en: $xamppPath" -ForegroundColor Green
} else {
    Write-Host "ERROR - XAMPP no encontrado en C:\xampp" -ForegroundColor Red
    Write-Host "Instala XAMPP desde: https://www.apachefriends.org/" -ForegroundColor Yellow
}

# Verificar MySQL
Write-Host ""
Write-Host "2. Verificando MySQL..." -ForegroundColor Cyan
try {
    $mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($mysqlProcess) {
        Write-Host "OK - MySQL ejecutandose (PID: $($mysqlProcess.Id))" -ForegroundColor Green
    } else {
        Write-Host "ADVERTENCIA - MySQL no esta ejecutandose" -ForegroundColor Yellow
        Write-Host "Inicia MySQL desde el panel de control de XAMPP" -ForegroundColor Yellow
    }
} catch {
    Write-Host "ERROR - Error al verificar MySQL" -ForegroundColor Red
}

# Verificar conexión MySQL
Write-Host ""
Write-Host "3. Verificando conexion MySQL..." -ForegroundColor Cyan
try {
    $mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
    if (Test-Path $mysqlExe) {
        $result = & $mysqlExe -u root -e "SELECT VERSION();" 2>$null
        if ($result) {
            Write-Host "OK - Conexion MySQL exitosa" -ForegroundColor Green
            Write-Host "Version: $($result[1])" -ForegroundColor White
        } else {
            Write-Host "ERROR - No se pudo conectar a MySQL" -ForegroundColor Red
        }
    } else {
        Write-Host "ERROR - MySQL no encontrado en $mysqlExe" -ForegroundColor Red
    }
} catch {
    Write-Host "ERROR - Error de conexion MySQL: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar base de datos
Write-Host ""
Write-Host "4. Verificando base de datos csdt_local..." -ForegroundColor Cyan
try {
    $mysqlExe = "C:\xampp\mysql\bin\mysql.exe"
    if (Test-Path $mysqlExe) {
        $dbExists = & $mysqlExe -u root -e "SHOW DATABASES LIKE 'csdt_local';" 2>$null
        if ($dbExists -match "csdt_local") {
            Write-Host "OK - Base de datos csdt_local existe" -ForegroundColor Green
            
            # Verificar tablas
            $tables = & $mysqlExe -u root -D csdt_local -e "SHOW TABLES;" 2>$null
            if ($tables) {
                $tableCount = ($tables | Measure-Object).Count
                Write-Host "OK - Tablas encontradas: $tableCount" -ForegroundColor Green
                $tables | ForEach-Object { 
                    if ($_ -notmatch "Tables_in_csdt_local") {
                        Write-Host "  - $_" -ForegroundColor White 
                    }
                }
            } else {
                Write-Host "ADVERTENCIA - No se encontraron tablas en la base de datos" -ForegroundColor Yellow
            }
        } else {
            Write-Host "ADVERTENCIA - Base de datos csdt_local no existe" -ForegroundColor Yellow
            Write-Host "Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "ERROR - Error al verificar base de datos: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar archivo .env
Write-Host ""
Write-Host "5. Verificando archivo .env..." -ForegroundColor Cyan
if (Test-Path ".env") {
    Write-Host "OK - Archivo .env existe" -ForegroundColor Green
    
    $envContent = Get-Content ".env" -Raw
    if ($envContent -match "DB_CONNECTION=mysql") {
        Write-Host "OK - Configuracion MySQL en .env" -ForegroundColor Green
    } else {
        Write-Host "ADVERTENCIA - Configuracion MySQL no encontrada en .env" -ForegroundColor Yellow
    }
    
    if ($envContent -match "DB_DATABASE=csdt_local") {
        Write-Host "OK - Base de datos configurada como csdt_local" -ForegroundColor Green
    } else {
        Write-Host "ADVERTENCIA - Base de datos no configurada como csdt_local" -ForegroundColor Yellow
    }
} else {
    Write-Host "ERROR - Archivo .env no existe" -ForegroundColor Red
    Write-Host "Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor Yellow
}

# Verificar PHP
Write-Host ""
Write-Host "6. Verificando PHP..." -ForegroundColor Cyan
try {
    $phpVersion = & php -v 2>$null
    if ($phpVersion) {
        Write-Host "OK - PHP disponible" -ForegroundColor Green
        $version = ($phpVersion[0] -split " ")[1]
        Write-Host "Version: $version" -ForegroundColor White
    } else {
        Write-Host "ERROR - PHP no disponible" -ForegroundColor Red
        Write-Host "Asegurate de que PHP este en el PATH" -ForegroundColor Yellow
    }
} catch {
    Write-Host "ERROR - Error al verificar PHP: $($_.Exception.Message)" -ForegroundColor Red
}

# Verificar Composer
Write-Host ""
Write-Host "7. Verificando Composer..." -ForegroundColor Cyan
try {
    $composerVersion = & composer --version 2>$null
    if ($composerVersion) {
        Write-Host "OK - Composer disponible" -ForegroundColor Green
        Write-Host "Version: $($composerVersion[0])" -ForegroundColor White
    } else {
        Write-Host "ERROR - Composer no disponible" -ForegroundColor Red
        Write-Host "Instala Composer desde: https://getcomposer.org/" -ForegroundColor Yellow
    }
} catch {
    Write-Host "ERROR - Error al verificar Composer: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== VERIFICACION COMPLETADA ===" -ForegroundColor Green
Write-Host ""
Write-Host "Proximos pasos:" -ForegroundColor Cyan
Write-Host "1. Si hay errores, corrijelos antes de continuar" -ForegroundColor White
Write-Host "2. Ejecuta: .\scripts\configurar-xampp-mysql.ps1" -ForegroundColor White
Write-Host "3. Luego: .\scripts\ejecutar-migraciones-xampp.ps1" -ForegroundColor White
Write-Host "4. Finalmente: php artisan serve" -ForegroundColor White
