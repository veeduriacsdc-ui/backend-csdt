# Script de configuración automática para XAMPP
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

Write-Host "🚀 CONFIGURACIÓN AUTOMÁTICA XAMPP PARA CSDT" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

# Verificar si XAMPP está ejecutándose
Write-Host "1️⃣ Verificando servicios de XAMPP..." -ForegroundColor Yellow
$apacheProcess = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
$mysqlProcess = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue

if ($apacheProcess) {
    Write-Host "✅ Apache está ejecutándose" -ForegroundColor Green
} else {
    Write-Host "❌ Apache no está ejecutándose" -ForegroundColor Red
    Write-Host "💡 Inicia XAMPP Control Panel y activa Apache" -ForegroundColor Cyan
}

if ($mysqlProcess) {
    Write-Host "✅ MySQL está ejecutándose" -ForegroundColor Green
} else {
    Write-Host "❌ MySQL no está ejecutándose" -ForegroundColor Red
    Write-Host "💡 Inicia XAMPP Control Panel y activa MySQL" -ForegroundColor Cyan
}

# Verificar conexión a MySQL
Write-Host ""
Write-Host "2️⃣ Verificando conexión a MySQL..." -ForegroundColor Yellow

try {
    $connection = New-Object System.Data.Odbc.OdbcConnection
    $connection.ConnectionString = "Driver={MySQL ODBC 8.0 Unicode Driver};Server=127.0.0.1;Port=3306;Database=mysql;User=root;Password=;"
    $connection.Open()
    $connection.Close()
    Write-Host "✅ Conexión a MySQL exitosa" -ForegroundColor Green
} catch {
    Write-Host "❌ No se pudo conectar a MySQL" -ForegroundColor Red
    Write-Host "💡 Verifica que MySQL esté ejecutándose en XAMPP" -ForegroundColor Cyan
}

# Crear base de datos si no existe
Write-Host ""
Write-Host "3️⃣ Creando base de datos..." -ForegroundColor Yellow

$createDbScript = @"
CREATE DATABASE IF NOT EXISTS csdt_veeduria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE csdt_veeduria;
SHOW TABLES;
"@

try {
    $createDbScript | Out-File -FilePath "temp_create_db.sql" -Encoding UTF8
    & "C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS csdt_veeduria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    Write-Host "✅ Base de datos 'csdt_veeduria' creada/verificada" -ForegroundColor Green
    Remove-Item "temp_create_db.sql" -ErrorAction SilentlyContinue
} catch {
    Write-Host "❌ Error al crear base de datos" -ForegroundColor Red
    Write-Host "💡 Ejecuta manualmente: CREATE DATABASE csdt_veeduria;" -ForegroundColor Cyan
}

# Verificar archivo .env
Write-Host ""
Write-Host "4️⃣ Verificando configuración .env..." -ForegroundColor Yellow

if (Test-Path ".env") {
    Write-Host "✅ Archivo .env encontrado" -ForegroundColor Green
} else {
    Write-Host "❌ Archivo .env no encontrado" -ForegroundColor Red
    Write-Host "📝 Creando archivo .env..." -ForegroundColor Cyan
    
    $envContent = @"
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Bogota
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_veeduria
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
"@
    
    $envContent | Out-File -FilePath ".env" -Encoding UTF8
    Write-Host "✅ Archivo .env creado" -ForegroundColor Green
}

# Ejecutar comandos de Laravel
Write-Host ""
Write-Host "5️⃣ Ejecutando comandos de Laravel..." -ForegroundColor Yellow

$comandos = @(
    "php artisan key:generate",
    "php artisan config:clear",
    "php artisan cache:clear",
    "php artisan migrate:fresh --seed"
)

foreach ($comando in $comandos) {
    Write-Host "💻 Ejecutando: $comando" -ForegroundColor Cyan
    try {
        $resultado = Invoke-Expression $comando 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✅ Comando ejecutado exitosamente" -ForegroundColor Green
        } else {
            Write-Host "❌ Error en comando: $resultado" -ForegroundColor Red
        }
    } catch {
        Write-Host "❌ Error al ejecutar comando: $_" -ForegroundColor Red
    }
}

# Verificar que todo esté funcionando
Write-Host ""
Write-Host "6️⃣ Verificación final..." -ForegroundColor Yellow

# Verificar que las tablas se crearon
try {
    $tablas = & "C:\xampp\mysql\bin\mysql.exe" -u root -D csdt_veeduria -e "SHOW TABLES;" 2>$null
    if ($tablas -match "clientes|operadores|pqrsfd") {
        Write-Host "✅ Tablas principales creadas correctamente" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Algunas tablas no se crearon" -ForegroundColor Yellow
    }
} catch {
    Write-Host "❌ No se pudo verificar las tablas" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 CONFIGURACIÓN COMPLETADA" -ForegroundColor Green
Write-Host "===========================" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 URLs para probar:" -ForegroundColor Cyan
Write-Host "• Backend: http://localhost:8000" -ForegroundColor White
Write-Host "• Frontend: http://localhost:5173" -ForegroundColor White
Write-Host "• Base de datos: csdt_veeduria" -ForegroundColor White
Write-Host ""
Write-Host "👤 Usuario por defecto:" -ForegroundColor Cyan
Write-Host "• Email: admin@csdt.com" -ForegroundColor White
Write-Host "• Contraseña: password" -ForegroundColor White
Write-Host ""
Write-Host "🚀 Para iniciar el sistema:" -ForegroundColor Cyan
Write-Host "• Backend: php artisan serve --host=127.0.0.1 --port=8000" -ForegroundColor White
Write-Host "• Frontend: cd ../frontend-csdt-final && npm run dev" -ForegroundColor White
Write-Host ""
