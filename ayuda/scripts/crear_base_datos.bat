@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE CREACIÓN DE BASE DE DATOS
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%CREACIÓN DE BASE DE DATOS%NC%
echo %BLUE%===========================================%NC%

REM ===========================================
REM CREAR BASE DE DATOS MYSQL
REM ===========================================
echo %GREEN%[INFO]%NC% Creando base de datos MySQL...

REM Crear archivo SQL para creación de base de datos
(
echo -- Crear base de datos CSDT
echo CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
echo.
echo -- Crear usuario CSDT
echo CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY '123';
echo CREATE USER IF NOT EXISTS 'csdt'@'%%' IDENTIFIED BY '123';
echo.
echo -- Otorgar permisos
echo GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
echo GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'%%';
echo.
echo -- Aplicar cambios
echo FLUSH PRIVILEGES;
echo.
echo -- Verificar creación
echo SHOW DATABASES LIKE 'csdt_final';
echo SELECT User, Host FROM mysql.user WHERE User = 'csdt';
) > "C:\temp\crear_base_datos.sql"

REM Ejecutar creación de base de datos
mysql -u root -e "source C:\temp\crear_base_datos.sql"

if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Base de datos creada exitosamente
) else (
    echo %RED%[ERROR]%NC% ❌ Error al crear la base de datos
    pause
    exit /b 1
)

REM ===========================================
REM CREAR ARCHIVO .ENV
REM ===========================================
echo %GREEN%[INFO]%NC% Creando archivo .env...

cd /d "%~dp0..\.."

REM Crear .env si no existe
if not exist ".env" (
    copy ".env.example" ".env"
)

REM Configurar base de datos en .env
powershell -Command "(Get-Content .env) -replace 'DB_CONNECTION=.*', 'DB_CONNECTION=mysql' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_HOST=.*', 'DB_HOST=127.0.0.1' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_PORT=.*', 'DB_PORT=3306' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_DATABASE=.*', 'DB_DATABASE=csdt_final' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_USERNAME=.*', 'DB_USERNAME=csdt' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'DB_PASSWORD=.*', 'DB_PASSWORD=123' | Set-Content .env"

REM Configurar Redis
powershell -Command "(Get-Content .env) -replace 'REDIS_HOST=.*', 'REDIS_HOST=127.0.0.1' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'REDIS_PORT=.*', 'REDIS_PORT=6379' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'CACHE_DRIVER=.*', 'CACHE_DRIVER=redis' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'SESSION_DRIVER=.*', 'SESSION_DRIVER=redis' | Set-Content .env"
powershell -Command "(Get-Content .env) -replace 'QUEUE_CONNECTION=.*', 'QUEUE_CONNECTION=redis' | Set-Content .env"

echo %GREEN%[SUCCESS]%NC% ✅ Archivo .env configurado

REM ===========================================
REM GENERAR CLAVE DE APLICACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Generando clave de aplicación...

C:\php\php.exe artisan key:generate

echo %GREEN%[SUCCESS]%NC% ✅ Clave de aplicación generada

REM ===========================================
REM EJECUTAR MIGRACIONES
REM ===========================================
echo %GREEN%[INFO]%NC% Ejecutando migraciones...

C:\php\php.exe artisan migrate --force

if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Migraciones ejecutadas exitosamente
) else (
    echo %RED%[ERROR]%NC% ❌ Error al ejecutar migraciones
    pause
    exit /b 1
)

REM ===========================================
REM EJECUTAR SEEDERS
REM ===========================================
echo %GREEN%[INFO]%NC% Ejecutando seeders...

C:\php\php.exe artisan db:seed --force

if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Seeders ejecutados exitosamente
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ Advertencia al ejecutar seeders
)

REM ===========================================
REM CREAR ENLACE SIMBÓLICO DE STORAGE
REM ===========================================
echo %GREEN%[INFO]%NC% Creando enlace simbólico de storage...

C:\php\php.exe artisan storage:link

echo %GREEN%[SUCCESS]%NC% ✅ Enlace simbólico creado

REM ===========================================
REM CACHEAR CONFIGURACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Cacheando configuración...

C:\php\php.exe artisan config:cache
C:\php\php.exe artisan route:cache
C:\php\php.exe artisan view:cache

echo %GREEN%[SUCCESS]%NC% ✅ Configuración cacheada

REM ===========================================
REM VERIFICAR CONFIGURACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando configuración...

REM Verificar conexión a base de datos
mysql -u csdt -p123 -e "SELECT 1;" >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Conexión a base de datos exitosa
) else (
    echo %RED%[ERROR]%NC% ❌ Error de conexión a base de datos
)

REM Verificar tablas
for /f %%i in ('mysql -u csdt -p123 csdt_final -e "SHOW TABLES;" ^| find /c /v ""') do set TABLES=%%i
echo %GREEN%[INFO]%NC% Número de tablas creadas: %TABLES%

REM ===========================================
REM LIMPIAR ARCHIVOS TEMPORALES
REM ===========================================
echo %GREEN%[INFO]%NC% Limpiando archivos temporales...

del "C:\temp\crear_base_datos.sql" 2>nul

echo %GREEN%[SUCCESS]%NC% ✅ Archivos temporales limpiados

echo %BLUE%===========================================%NC%
echo %BLUE%CREACIÓN DE BASE DE DATOS COMPLETADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Base de datos configurada correctamente
echo %GREEN%[INFO]%NC% Base de datos: csdt_final
echo %GREEN%[INFO]%NC% Usuario: csdt
echo %GREEN%[INFO]%NC% Contraseña: 123
echo %GREEN%[INFO]%NC% Host: localhost
echo %GREEN%[INFO]%NC% Puerto: 3306

pause
