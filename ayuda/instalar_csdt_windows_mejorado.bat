@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE INSTALACIÓN CSDT WINDOWS MEJORADO
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM VERSIÓN WINDOWS - MEJORADA
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "PURPLE=[95m"
set "CYAN=[96m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN CSDT WINDOWS - VERSIÓN MEJORADA%NC%
echo %BLUE%===========================================%NC%

REM Verificar que estamos como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%[ERROR]%NC% Por favor ejecuta este script como administrador
    pause
    exit /b 1
)

REM ===========================================
REM CREAR ESTRUCTURA DE CARPETAS
REM ===========================================
echo %PURPLE%[PASO]%NC% Creando estructura de carpetas...

REM Crear carpetas principales
if not exist "C:\var\www\backend-csdt" mkdir "C:\var\www\backend-csdt"
if not exist "C:\var\www\frontend-csdt" mkdir "C:\var\www\frontend-csdt"
if not exist "C:\var\log\backend-csdt" mkdir "C:\var\log\backend-csdt"
if not exist "C:\var\log\frontend-csdt" mkdir "C:\var\log\frontend-csdt"
if not exist "C:\var\backups\database" mkdir "C:\var\backups\database"

echo %GREEN%[SUCCESS]%NC% Estructura de carpetas creada

REM ===========================================
REM INSTALAR CHOCOLATEY
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando Chocolatey...

REM Verificar si Chocolatey está instalado
where choco >nul 2>&1
if %errorLevel% neq 0 (
    echo %GREEN%[INFO]%NC% Instalando Chocolatey...
    powershell -Command "Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))"
) else (
    echo %GREEN%[INFO]%NC% Chocolatey ya está instalado
)

REM ===========================================
REM INSTALAR DEPENDENCIAS BÁSICAS
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias básicas...

REM Instalar herramientas básicas
choco install -y curl wget git unzip 7zip
choco install -y python3 nodejs-lts
choco install -y mysql redis
choco install -y visualstudio2022buildtools

REM Instalar PHP
choco install -y php --params "/InstallDir:C:\php"

REM Instalar Composer
choco install -y composer

REM Instalar PM2
npm install -g pm2

echo %GREEN%[SUCCESS]%NC% Dependencias básicas instaladas

REM ===========================================
REM CONFIGURAR VARIABLES DE ENTORNO
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando variables de entorno...

REM Agregar PHP al PATH
setx PATH "%PATH%;C:\php" /M

REM Configurar npm
npm config set prefix C:\npm-global
setx PATH "%PATH%;C:\npm-global" /M

echo %GREEN%[SUCCESS]%NC% Variables de entorno configuradas

REM ===========================================
REM CONFIGURAR SERVICIOS
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando servicios...

REM Iniciar MySQL como servicio
sc create MySQL binPath= "C:\ProgramData\chocolatey\lib\mysql\tools\bin\mysqld.exe --install" start= auto
sc start MySQL

REM Iniciar Redis como servicio
sc create Redis binPath= "C:\ProgramData\chocolatey\lib\redis\tools\redis-server.exe" start= auto
sc start Redis

echo %GREEN%[SUCCESS]%NC% Servicios configurados

REM ===========================================
REM INSTALAR DEPENDENCIAS DEL BACKEND
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias del backend...

cd /d "%~dp0..\.."

REM Instalar dependencias PHP
composer install --optimize-autoloader --no-dev

REM Instalar dependencias Node.js
npm install

echo %GREEN%[SUCCESS]%NC% Dependencias del backend instaladas

REM ===========================================
REM INSTALAR DEPENDENCIAS DEL FRONTEND
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias del frontend...

cd /d "C:\var\www\frontend-csdt"

REM Copiar archivos del frontend si no existen
if not exist "package.json" (
    echo %YELLOW%[WARNING]%NC% Frontend no encontrado. Copiando desde ubicación actual...
    xcopy "%~dp0..\..\..\frontend-csdt-final\*" "C:\var\www\frontend-csdt\" /E /I /Y
)

REM Instalar dependencias
npm install

echo %GREEN%[SUCCESS]%NC% Dependencias del frontend instaladas

REM ===========================================
REM CONFIGURAR BASE DE DATOS
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando base de datos...

REM Esperar a que MySQL se inicie
timeout /t 10 /nobreak >nul

REM Crear base de datos
mysql -u root -e "CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -e "CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY '123';"
mysql -u root -e "GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

echo %GREEN%[SUCCESS]%NC% Base de datos configurada

REM ===========================================
REM CONFIGURAR LARAVEL
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando Laravel...

cd /d "C:\var\www\backend-csdt"

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

REM Generar clave de aplicación
C:\php\php.exe artisan key:generate

REM Ejecutar migraciones
C:\php\php.exe artisan migrate --force

REM Crear enlace simbólico
C:\php\php.exe artisan storage:link

echo %GREEN%[SUCCESS]%NC% Laravel configurado

REM ===========================================
REM CONFIGURAR FRONTEND
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando frontend...

cd /d "C:\var\www\frontend-csdt"

REM Crear .env si no existe
if not exist ".env" (
    echo VITE_API_URL=http://localhost:8000/api > .env
    echo VITE_APP_NAME=CSDT >> .env
    echo VITE_APP_VERSION=1.0.0 >> .env
)

echo %GREEN%[SUCCESS]%NC% Frontend configurado

REM ===========================================
REM CONFIGURAR PM2
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando PM2...

REM Iniciar backend con PM2
cd /d "C:\var\www\backend-csdt"
pm2 start ecosystem.config.js --env production

REM Iniciar frontend con PM2
cd /d "C:\var\www\frontend-csdt"
pm2 start ecosystem-frontend.config.js

REM Guardar configuración PM2
pm2 save

echo %GREEN%[SUCCESS]%NC% PM2 configurado

REM ===========================================
REM VERIFICAR INSTALACIÓN
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando instalación...

REM Verificar servicios
pm2 status

REM Verificar conectividad
timeout /t 5 /nobreak >nul
curl -I http://localhost:8000 2>nul && echo %GREEN%[SUCCESS]%NC% ✅ Backend: http://localhost:8000 || echo %YELLOW%[WARNING]%NC% ⚠️ Backend no responde

curl -I http://localhost:3000 2>nul && echo %GREEN%[SUCCESS]%NC% ✅ Frontend: http://localhost:3000 || echo %YELLOW%[WARNING]%NC% ⚠️ Frontend no responde

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN COMPLETADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Sistema CSDT instalado correctamente
echo %GREEN%[INFO]%NC% Backend: http://localhost:8000
echo %GREEN%[INFO]%NC% Frontend: http://localhost:3000
echo %GREEN%[INFO]%NC% Base de datos: csdt_final
echo %GREEN%[INFO]%NC% Usuario: csdt / Contraseña: 123

echo %YELLOW%[WARNING]%NC% COMANDOS ÚTILES:
echo %YELLOW%[WARNING]%NC% Gestión: gestionar_csdt.bat
echo %YELLOW%[WARNING]%NC% Inicio: iniciar_csdt.bat
echo %YELLOW%[WARNING]%NC% Parada: detener_csdt.bat
echo %YELLOW%[WARNING]%NC% Verificación: verificar_csdt.bat

pause
