@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE VERIFICACIÓN COMPLETA DE DESPLIEGUE
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM WINDOWS - VERIFICACIÓN COMPLETA
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
echo %BLUE%VERIFICACIÓN COMPLETA DE DESPLIEGUE CSDT%NC%
echo %BLUE%===========================================%NC%

REM ===========================================
REM VERIFICAR SISTEMA OPERATIVO
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando sistema operativo...

echo %CYAN%=== INFORMACIÓN DEL SISTEMA ===%NC%
echo Sistema: %OS%
echo Arquitectura: %PROCESSOR_ARCHITECTURE%
echo Usuario: %USERNAME%
echo Directorio: %CD%

REM ===========================================
REM VERIFICAR HERRAMIENTAS BÁSICAS
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando herramientas básicas...

echo %CYAN%=== HERRAMIENTAS BÁSICAS ===%NC%

REM Verificar PHP
where php >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ PHP instalado
    C:\php\php.exe --version
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
echo %PURPLE%[PASO]%NC% Verificando servicios del sistema...

echo %CYAN%=== SERVICIOS DEL SISTEMA ===%NC%

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
echo %PURPLE%[PASO]%NC% Verificando proyecto backend...

echo %CYAN%=== PROYECTO BACKEND ===%NC%

if exist "C:\var\www\backend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio backend existe
    
    REM Verificar archivos críticos
    if exist "C:\var\www\backend-csdt\artisan" (
        echo %GREEN%[SUCCESS]%NC% ✅ artisan existe
    ) else (
        echo %RED%[ERROR]%NC% ❌ artisan no encontrado
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
        echo %RED%[ERROR]%NC% ❌ .env no encontrado
    )
    
    REM Verificar dependencias
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
echo %PURPLE%[PASO]%NC% Verificando proyecto frontend...

echo %CYAN%=== PROYECTO FRONTEND ===%NC%

if exist "C:\var\www\frontend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio frontend existe
    
    REM Verificar archivos críticos
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
    
    REM Verificar dependencias
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
echo %PURPLE%[PASO]%NC% Verificando conectividad...

echo %CYAN%=== CONECTIVIDAD ===%NC%

REM Verificar backend
curl -I http://localhost:8000 2>nul
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Backend: http://localhost:8000 respondiendo
) else (
    echo %RED%[ERROR]%NC% ❌ Backend: http://localhost:8000 no responde
)

REM Verificar frontend
curl -I http://localhost:3000 2>nul
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Frontend: http://localhost:3000 respondiendo
) else (
    echo %RED%[ERROR]%NC% ❌ Frontend: http://localhost:3000 no responde
)

REM ===========================================
REM VERIFICAR BASE DE DATOS
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando base de datos...

echo %CYAN%=== BASE DE DATOS ===%NC%

mysql -u csdt -p123 -e "SELECT 1;" >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Conexión a base de datos exitosa
) else (
    echo %RED%[ERROR]%NC% ❌ Error de conexión a base de datos
)

REM ===========================================
REM VERIFICAR PM2
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando PM2...

echo %CYAN%=== PM2 ===%NC%

where pm2 >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ PM2 instalado
    pm2 status
) else (
    echo %RED%[ERROR]%NC% ❌ PM2 no instalado
)

REM ===========================================
REM VERIFICAR LOGS
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando logs...

echo %CYAN%=== LOGS ===%NC%

if exist "C:\var\log\backend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio de logs backend existe
) else (
    echo %RED%[ERROR]%NC% ❌ Directorio de logs backend no existe
)

if exist "C:\var\log\frontend-csdt" (
    echo %GREEN%[SUCCESS]%NC% ✅ Directorio de logs frontend existe
) else (
    echo %RED%[ERROR]%NC% ❌ Directorio de logs frontend no existe
)

REM ===========================================
REM VERIFICAR SERVICIOS DE IA
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando servicios de IA...

echo %CYAN%=== SERVICIOS DE IA ===%NC%

if exist "C:\var\www\backend-csdt\config\ia_services.php" (
    echo %GREEN%[SUCCESS]%NC% ✅ Configuración de IA existe
) else (
    echo %RED%[ERROR]%NC% ❌ Configuración de IA no encontrada
)

REM ===========================================
REM VERIFICAR RENDIMIENTO
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando rendimiento...

echo %CYAN%=== RENDIMIENTO ===%NC%

REM Verificar memoria
wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /format:table

REM ===========================================
REM RESUMEN FINAL
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%RESUMEN DE VERIFICACIÓN%NC%
echo %BLUE%===========================================%NC%

echo %CYAN%=== ESTADO GENERAL ===%NC%
echo %GREEN%[SUCCESS]%NC% ✅ Verificación completada

echo %CYAN%=== INFORMACIÓN DE ACCESO ===%NC%
echo Backend local: http://localhost:8000
echo Frontend local: http://localhost:3000

echo %CYAN%=== COMANDOS ÚTILES ===%NC%
echo Gestión: gestionar_csdt.bat
echo Inicio: iniciar_csdt.bat
echo Parada: detener_csdt.bat
echo Verificación: verificar_csdt.bat
echo Monitoreo IA: monitor_ia.bat

echo %BLUE%===========================================%NC%
echo %BLUE%VERIFICACIÓN COMPLETADA%NC%
echo %BLUE%===========================================%NC%

pause
