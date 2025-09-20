@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE VERIFICACIÓN CSDT
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%VERIFICACIÓN CSDT%NC%
echo %BLUE%===========================================%NC%

REM ===========================================
REM VERIFICAR SISTEMA OPERATIVO
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando sistema operativo...
echo Sistema: %OS%
echo Arquitectura: %PROCESSOR_ARCHITECTURE%
echo Usuario: %USERNAME%

REM ===========================================
REM VERIFICAR HERRAMIENTAS BÁSICAS
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando herramientas básicas...

REM Verificar Chocolatey
where choco >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Chocolatey instalado
    choco --version
) else (
    echo %RED%[ERROR]%NC% ❌ Chocolatey no instalado
)

REM Verificar PHP
where php >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ PHP instalado
    php --version
) else (
    echo %RED%[ERROR]%NC% ❌ PHP no instalado
)

REM Verificar Composer
where composer >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Composer instalado
    composer --version
) else (
    echo %RED%[ERROR]%NC% ❌ Composer no instalado
)

REM Verificar Node.js
where node >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Node.js instalado
    node --version
) else (
    echo %RED%[ERROR]%NC% ❌ Node.js no instalado
)

REM Verificar NPM
where npm >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ NPM instalado
    npm --version
) else (
    echo %RED%[ERROR]%NC% ❌ NPM no instalado
)

REM Verificar PM2
where pm2 >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ PM2 instalado
    pm2 --version
) else (
    echo %RED%[ERROR]%NC% ❌ PM2 no instalado
)

REM ===========================================
REM VERIFICAR SERVICIOS
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando servicios...

REM Verificar MySQL
sc query MySQL >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ MySQL instalado como servicio
    sc query MySQL | findstr "RUNNING" >nul && echo %GREEN%[SUCCESS]%NC% ✅ MySQL ejecutándose || echo %YELLOW%[WARNING]%NC% ⚠️ MySQL no ejecutándose
) else (
    echo %RED%[ERROR]%NC% ❌ MySQL no instalado como servicio
)

REM Verificar Redis
sc query Redis >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Redis instalado como servicio
    sc query Redis | findstr "RUNNING" >nul && echo %GREEN%[SUCCESS]%NC% ✅ Redis ejecutándose || echo %YELLOW%[WARNING]%NC% ⚠️ Redis no ejecutándose
) else (
    echo %RED%[ERROR]%NC% ❌ Redis no instalado como servicio
)

REM ===========================================
REM VERIFICAR PROYECTO BACKEND
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando proyecto backend...

if exist "C:\var\www\backend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio backend existe
    
    if exist "C:\var\www\backend-csdt\artisan" (
        echo %GREEN%[SUCCESS]%NC% ✅ Archivo artisan existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ Archivo artisan no encontrado
    )
    
    if exist "C:\var\www\backend-csdt\composer.json" (
        echo %GREEN%[SUCCESS]%NC% ✅ composer.json existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ composer.json no encontrado
    )
    
    if exist "C:\var\www\backend-csdt\ecosystem.config.js" (
        echo %GREEN%[SUCCESS]%NC% ✅ ecosystem.config.js existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ ecosystem.config.js no encontrado
    )
    
    if exist "C:\var\www\backend-csdt\.env" (
        echo %GREEN%[SUCCESS]%NC% ✅ .env existe
    ) else (
        echo %YELLOW%[WARNING]%NC% ⚠️ .env no encontrado
    )
    
    if exist "C:\var\www\backend-csdt\vendor" (
        echo %GREEN%[SUCCESS]%NC% ✅ Dependencias PHP instaladas
    ) else (
        echo %RED%[ERROR]%NC% ❌ Dependencias PHP no instaladas
    )
    
    if exist "C:\var\www\backend-csdt\node_modules" (
        echo %GREEN%[SUCCESS]%NC% ✅ Dependencias Node.js instaladas
    ) else (
        echo %RED%[ERROR]%NC% ❌ Dependencias Node.js no instaladas
    )
) else (
    echo %RED%[ERROR]%NC% ❌ Directorio backend no existe
)

REM ===========================================
REM VERIFICAR PROYECTO FRONTEND
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando proyecto frontend...

if exist "C:\var\www\frontend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio frontend existe
    
    if exist "C:\var\www\frontend-csdt\package.json" (
        echo %GREEN%[SUCCESS]%NC% ✅ package.json existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ package.json no encontrado
    )
    
    if exist "C:\var\www\frontend-csdt\ecosystem-frontend.config.js" (
        echo %GREEN%[SUCCESS]%NC% ✅ ecosystem-frontend.config.js existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ ecosystem-frontend.config.js no encontrado
    )
    
    if exist "C:\var\www\frontend-csdt\node_modules" (
        echo %GREEN%[SUCCESS]%NC% ✅ Dependencias instaladas
    ) else (
        echo %RED%[ERROR]%NC% ❌ Dependencias no instaladas
    )
) else (
    echo %RED%[ERROR]%NC% ❌ Directorio frontend no existe
)

REM ===========================================
REM VERIFICAR CONECTIVIDAD
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando conectividad...

REM Verificar backend
curl -I http://localhost:8000 2>nul
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Backend respondiendo en http://localhost:8000
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ Backend no responde en http://localhost:8000
)

REM Verificar frontend
curl -I http://localhost:3000 2>nul
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Frontend respondiendo en http://localhost:3000
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ Frontend no responde en http://localhost:3000
)

REM ===========================================
REM VERIFICAR BASE DE DATOS
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando base de datos...

mysql -u csdt -p123 -e "SELECT 1;" >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Conexión a base de datos exitosa
    
    REM Verificar tablas
    for /f %%i in ('mysql -u csdt -p123 csdt_final -e "SHOW TABLES;" ^| find /c /v ""') do set TABLES=%%i
    echo %GREEN%[INFO]%NC% Número de tablas: %TABLES%
) else (
    echo %RED%[ERROR]%NC% ❌ Error de conexión a base de datos
)

REM ===========================================
REM VERIFICAR PM2
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando PM2...

pm2 status >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ PM2 funcionando
    pm2 status
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ PM2 no está funcionando
)

REM ===========================================
REM VERIFICAR LOGS
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando logs...

if exist "C:\var\log\backend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio de logs backend existe
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ Directorio de logs backend no existe
)

if exist "C:\var\log\frontend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio de logs frontend existe
) else (
    echo %YELLOW%[WARNING]%NC% ⚠️ Directorio de logs frontend no existe
)

REM ===========================================
REM RESUMEN
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%RESUMEN DE VERIFICACIÓN%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[INFO]%NC% Sistema: Windows
echo %GREEN%[INFO]%NC% Backend: C:\var\www\backend-csdt
echo %GREEN%[INFO]%NC% Frontend: C:\var\www\frontend-csdt
echo %GREEN%[INFO]%NC% Base de datos: csdt_final
echo %GREEN%[INFO]%NC% Usuario BD: csdt
echo %GREEN%[INFO]%NC% Logs: C:\var\log\

echo %YELLOW%[WARNING]%NC% Si hay errores, ejecuta:
echo %YELLOW%[WARNING]%NC% - instalar_csdt_windows_mejorado.bat
echo %YELLOW%[WARNING]%NC% - instalar_librerias.bat
echo %YELLOW%[WARNING]%NC% - configurar_base_datos.bat

pause
