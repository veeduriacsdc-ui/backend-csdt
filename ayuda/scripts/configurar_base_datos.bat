@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE CONFIGURACIÓN DE BASE DE DATOS
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%CONFIGURACIÓN DE BASE DE DATOS%NC%
echo %BLUE%===========================================%NC%

REM Verificar que estamos como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%[ERROR]%NC% Por favor ejecuta este script como administrador
    pause
    exit /b 1
)

REM ===========================================
REM CONFIGURAR MYSQL
REM ===========================================
echo %GREEN%[INFO]%NC% Configurando MySQL...

REM Verificar si MySQL está instalado
where mysql >nul 2>&1
if %errorLevel% neq 0 (
    echo %YELLOW%[WARNING]%NC% MySQL no está instalado. Instalando...
    choco install -y mysql
)

REM Iniciar MySQL como servicio
sc create MySQL binPath= "C:\ProgramData\chocolatey\lib\mysql\tools\bin\mysqld.exe --install" start= auto
sc start MySQL

REM Esperar a que MySQL se inicie
timeout /t 10 /nobreak >nul

echo %GREEN%[SUCCESS]%NC% ✅ MySQL configurado

REM ===========================================
REM CREAR BASE DE DATOS
REM ===========================================
echo %GREEN%[INFO]%NC% Creando base de datos...

REM Crear archivo SQL para configuración
(
echo -- Crear base de datos
echo CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
echo.
echo -- Crear usuario
echo CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY '123';
echo.
echo -- Otorgar permisos
echo GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
echo.
echo -- Aplicar cambios
echo FLUSH PRIVILEGES;
echo.
echo -- Verificar
echo SHOW DATABASES;
echo SELECT User, Host FROM mysql.user WHERE User = 'csdt';
) > "C:\temp\configurar_db.sql"

REM Ejecutar configuración de base de datos
mysql -u root -e "source C:\temp\configurar_db.sql"

echo %GREEN%[SUCCESS]%NC% ✅ Base de datos creada

REM ===========================================
REM EJECUTAR MIGRACIONES
REM ===========================================
echo %GREEN%[INFO]%NC% Ejecutando migraciones...

cd /d "%~dp0..\.."

REM Crear archivo .env si no existe
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

REM Generar clave de aplicación
C:\php\php.exe artisan key:generate

REM Ejecutar migraciones
C:\php\php.exe artisan migrate --force

echo %GREEN%[SUCCESS]%NC% ✅ Migraciones ejecutadas

REM ===========================================
REM EJECUTAR SEEDERS
REM ===========================================
echo %GREEN%[INFO]%NC% Ejecutando seeders...

REM Ejecutar seeders
C:\php\php.exe artisan db:seed --force

echo %GREEN%[SUCCESS]%NC% ✅ Seeders ejecutados

REM ===========================================
REM CREAR USUARIOS DE PRUEBA
REM ===========================================
echo %GREEN%[INFO]%NC% Creando usuarios de prueba...

REM Crear archivo SQL para usuarios
(
echo -- Usuario administrador
echo INSERT IGNORE INTO users ^(id, name, email, email_verified_at, password, created_at, updated_at^) 
echo VALUES ^(1, 'Administrador', 'admin@csdt.com', NOW^(^), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW^(^), NOW^(^)^);
echo.
echo -- Usuario operador
echo INSERT IGNORE INTO users ^(id, name, email, email_verified_at, password, created_at, updated_at^) 
echo VALUES ^(2, 'Operador', 'operador@csdt.com', NOW^(^), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW^(^), NOW^(^)^);
echo.
echo -- Usuario cliente
echo INSERT IGNORE INTO users ^(id, name, email, email_verified_at, password, created_at, updated_at^) 
echo VALUES ^(3, 'Cliente', 'cliente@csdt.com', NOW^(^), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW^(^), NOW^(^)^);
) > "C:\temp\crear_usuarios.sql"

REM Ejecutar creación de usuarios
mysql -u csdt -p123 csdt_final -e "source C:\temp\crear_usuarios.sql"

echo %GREEN%[SUCCESS]%NC% ✅ Usuarios de prueba creados

REM ===========================================
REM CONFIGURAR BACKUP AUTOMÁTICO
REM ===========================================
echo %GREEN%[INFO]%NC% Configurando backup automático...

REM Crear script de backup
(
echo @echo off
echo REM Script de backup automático de base de datos
echo.
echo set BACKUP_DIR=C:\var\backups\database
echo set DATE=%%date:~-4,4%%%%date:~-10,2%%%%date:~-7,2%%_%%time:~0,2%%%%time:~3,2%%%%time:~6,2%%
echo set BACKUP_FILE=%%BACKUP_DIR%%\csdt_database_%%DATE%%.sql
echo.
echo REM Crear directorio si no existe
echo if not exist "%%BACKUP_DIR%%" mkdir "%%BACKUP_DIR%%"
echo.
echo REM Hacer backup
echo mysqldump -u csdt -p123 csdt_final ^> "%%BACKUP_FILE%%"
echo.
echo REM Comprimir
echo powershell -Command "Compress-Archive -Path '%%BACKUP_FILE%%' -DestinationPath '%%BACKUP_FILE%%.zip' -Force"
echo.
echo REM Eliminar archivo original
echo del "%%BACKUP_FILE%%"
echo.
echo REM Eliminar backups antiguos ^(más de 7 días^)
echo forfiles /p "%%BACKUP_DIR%%" /m "csdt_database_*.sql.zip" /d -7 /c "cmd /c del @path"
echo.
echo echo Backup completado: %%BACKUP_FILE%%.zip
) > "C:\Windows\System32\backup_database.bat"

REM Configurar tarea programada para backup diario
schtasks /create /tn "CSDT_Backup_Database" /tr "C:\Windows\System32\backup_database.bat" /sc daily /st 02:00 /f

echo %GREEN%[SUCCESS]%NC% ✅ Backup automático configurado

REM ===========================================
REM OPTIMIZAR BASE DE DATOS
REM ===========================================
echo %GREEN%[INFO]%NC% Optimizando base de datos...

REM Crear archivo SQL para optimización
(
echo -- Optimizar tablas principales
echo OPTIMIZE TABLE users;
echo OPTIMIZE TABLE password_reset_tokens;
echo OPTIMIZE TABLE failed_jobs;
echo OPTIMIZE TABLE personal_access_tokens;
echo OPTIMIZE TABLE migrations;
echo OPTIMIZE TABLE sessions;
echo.
echo -- Verificar estado
echo SHOW TABLE STATUS;
) > "C:\temp\optimizar_db.sql"

REM Ejecutar optimización
mysql -u csdt -p123 csdt_final -e "source C:\temp\optimizar_db.sql"

echo %GREEN%[SUCCESS]%NC% ✅ Base de datos optimizada

REM ===========================================
REM CONFIGURAR MONITOREO
REM ===========================================
echo %GREEN%[INFO]%NC% Configurando monitoreo...

REM Crear script de monitoreo
(
echo @echo off
echo echo === MONITOR DE BASE DE DATOS ===
echo echo Fecha: %%date%% %%time%%
echo echo.
echo.
echo REM Verificar conexión
echo mysql -u csdt -p123 -e "SELECT 1;" ^>nul 2^>^&1
echo if %%errorLevel%% equ 0 ^(
echo     echo ✅ Conexión a base de datos exitosa
echo ^) else ^(
echo     echo ❌ Error de conexión a base de datos
echo     exit /b 1
echo ^)
echo.
echo echo === ESTADÍSTICAS ===
echo mysql -u csdt -p123 csdt_final -e "SELECT table_name, table_rows, ROUND^((^(data_length + index_length^) / 1024 / 1024^), 2^) AS 'Size ^(MB^)' FROM information_schema.tables WHERE table_schema = 'csdt_final' ORDER BY ^(data_length + index_length^) DESC;"
echo.
echo echo === PROCESOS ACTIVOS ===
echo mysql -u csdt -p123 csdt_final -e "SHOW PROCESSLIST;"
echo.
echo echo === VARIABLES IMPORTANTES ===
echo mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'max_connections';"
echo mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"
echo mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'query_cache_size';"
) > "C:\Windows\System32\monitor_database.bat"

echo %GREEN%[SUCCESS]%NC% ✅ Monitoreo configurado

REM ===========================================
REM VERIFICAR CONFIGURACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando configuración...

REM Verificar conexión
mysql -u csdt -p123 -e "SELECT 1;" >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Conexión a base de datos exitosa
) else (
    echo %RED%[ERROR]%NC% ❌ Error de conexión a base de datos
    pause
    exit /b 1
)

REM Verificar tablas
for /f %%i in ('mysql -u csdt -p123 csdt_final -e "SHOW TABLES;" ^| find /c /v ""') do set TABLES=%%i
echo %GREEN%[INFO]%NC% Número de tablas: %TABLES%

REM Verificar usuarios
for /f %%i in ('mysql -u csdt -p123 csdt_final -e "SELECT COUNT(*) FROM users;" ^| find /c /v ""') do set USERS=%%i
echo %GREEN%[INFO]%NC% Número de usuarios: %USERS%

REM Verificar configuración MySQL
echo %GREEN%[INFO]%NC% Configuración MySQL:
mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'max_connections';"
mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"

echo %GREEN%[SUCCESS]%NC% ✅ Configuración verificada

REM ===========================================
REM LIMPIAR ARCHIVOS TEMPORALES
REM ===========================================
echo %GREEN%[INFO]%NC% Limpiando archivos temporales...

del "C:\temp\configurar_db.sql" 2>nul
del "C:\temp\crear_usuarios.sql" 2>nul
del "C:\temp\optimizar_db.sql" 2>nul

echo %GREEN%[SUCCESS]%NC% ✅ Archivos temporales limpiados

echo %BLUE%===========================================%NC%
echo %BLUE%CONFIGURACIÓN DE BASE DE DATOS COMPLETADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Base de datos configurada correctamente
echo %GREEN%[INFO]%NC% Base de datos: csdt_final
echo %GREEN%[INFO]%NC% Usuario: csdt
echo %GREEN%[INFO]%NC% Contraseña: 123
echo %GREEN%[INFO]%NC% Host: localhost
echo %GREEN%[INFO]%NC% Puerto: 3306
echo %GREEN%[INFO]%NC% Para monitorear, ejecuta: monitor_database.bat

pause
