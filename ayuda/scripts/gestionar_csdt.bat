@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE GESTIÓN PRINCIPAL CSDT
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "PURPLE=[95m"
set "CYAN=[96m"
set "NC=[0m"

:menu
cls
echo %BLUE%===========================================%NC%
echo %BLUE%    GESTIÓN CSDT - %date% %time%    %NC%
echo %BLUE%===========================================%NC%
echo.
echo 1. Ver estado del sistema
echo 2. Iniciar servicios
echo 3. Detener servicios
echo 4. Reiniciar servicios
echo 5. Ver logs
echo 6. Verificar sistema
echo 7. Reparar sistema
echo 8. Limpiar sistema
echo 9. Hacer backup
echo 10. Monitorear en tiempo real
echo 11. Configurar base de datos
echo 12. Instalar dependencias
echo 13. Salir
echo.
set /p "option=Selecciona una opción (1-13): "

if "%option%"=="1" goto estado
if "%option%"=="2" goto iniciar
if "%option%"=="3" goto detener
if "%option%"=="4" goto reiniciar
if "%option%"=="5" goto logs
if "%option%"=="6" goto verificar
if "%option%"=="7" goto reparar
if "%option%"=="8" goto limpiar
if "%option%"=="9" goto backup
if "%option%"=="10" goto monitor
if "%option%"=="11" goto configurar_db
if "%option%"=="12" goto instalar_deps
if "%option%"=="13" goto salir
goto menu

:estado
cls
echo %BLUE%===========================================%NC%
echo %BLUE%ESTADO DEL SISTEMA%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Verificando estado de PM2...
pm2 status
echo.
echo %GREEN%[INFO]%NC% Verificando conectividad...
curl -I http://localhost:8000 2>nul && echo %GREEN%[SUCCESS]%NC% ✅ Backend: http://localhost:8000 || echo %YELLOW%[WARNING]%NC% ⚠️ Backend no responde
curl -I http://localhost:3000 2>nul && echo %GREEN%[SUCCESS]%NC% ✅ Frontend: http://localhost:3000 || echo %YELLOW%[WARNING]%NC% ⚠️ Frontend no responde
echo.
echo %GREEN%[INFO]%NC% Verificando servicios...
sc query MySQL | findstr "RUNNING" >nul && echo %GREEN%[SUCCESS]%NC% ✅ MySQL ejecutándose || echo %YELLOW%[WARNING]%NC% ⚠️ MySQL no ejecutándose
sc query Redis | findstr "RUNNING" >nul && echo %GREEN%[SUCCESS]%NC% ✅ Redis ejecutándose || echo %YELLOW%[WARNING]%NC% ⚠️ Redis no ejecutándose
echo.
pause
goto menu

:iniciar
cls
echo %BLUE%===========================================%NC%
echo %BLUE%INICIANDO SERVICIOS%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Iniciando servicios...
cd /d "C:\var\www\backend-csdt"
pm2 start ecosystem.config.js --env production
cd /d "C:\var\www\frontend-csdt"
pm2 start ecosystem-frontend.config.js
pm2 save
echo.
echo %GREEN%[SUCCESS]%NC% ✅ Servicios iniciados
echo.
pause
goto menu

:detener
cls
echo %BLUE%===========================================%NC%
echo %BLUE%DETENIENDO SERVICIOS%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Deteniendo servicios...
pm2 stop all
pm2 save
echo.
echo %GREEN%[SUCCESS]%NC% ✅ Servicios detenidos
echo.
pause
goto menu

:reiniciar
cls
echo %BLUE%===========================================%NC%
echo %BLUE%REINICIANDO SERVICIOS%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Reiniciando servicios...
pm2 restart all
pm2 save
echo.
echo %GREEN%[SUCCESS]%NC% ✅ Servicios reiniciados
echo.
pause
goto menu

:logs
cls
echo %BLUE%===========================================%NC%
echo %BLUE%VER LOGS%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Mostrando logs de PM2...
pm2 logs --lines 50
echo.
pause
goto menu

:verificar
cls
echo %BLUE%===========================================%NC%
echo %BLUE%VERIFICAR SISTEMA%NC%
echo %BLUE%===========================================%NC%
echo.
call "%~dp0verificar_csdt.bat"
echo.
pause
goto menu

:reparar
cls
echo %BLUE%===========================================%NC%
echo %BLUE%REPARAR SISTEMA%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Reparando sistema...
echo %GREEN%[INFO]%NC% 1. Deteniendo servicios...
pm2 stop all
echo %GREEN%[INFO]%NC% 2. Limpiando cache...
cd /d "C:\var\www\backend-csdt"
C:\php\php.exe artisan cache:clear
C:\php\php.exe artisan config:clear
C:\php\php.exe artisan route:clear
C:\php\php.exe artisan view:clear
echo %GREEN%[INFO]%NC% 3. Reiniciando servicios...
pm2 restart all
echo.
echo %GREEN%[SUCCESS]%NC% ✅ Sistema reparado
echo.
pause
goto menu

:limpiar
cls
echo %BLUE%===========================================%NC%
echo %BLUE%LIMPIAR SISTEMA%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Limpiando sistema...
echo %GREEN%[INFO]%NC% 1. Limpiando cache de Composer...
composer clear-cache
echo %GREEN%[INFO]%NC% 2. Limpiando cache de NPM...
npm cache clean --force
echo %GREEN%[INFO]%NC% 3. Limpiando logs antiguos...
forfiles /p "C:\var\log" /m "*.log" /d -7 /c "cmd /c del @path" 2>nul
echo.
echo %GREEN%[SUCCESS]%NC% ✅ Sistema limpiado
echo.
pause
goto menu

:backup
cls
echo %BLUE%===========================================%NC%
echo %BLUE%HACER BACKUP%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Haciendo backup...
set BACKUP_DIR=C:\var\backups\database
set DATE=%date:~-4,4%%date:~-10,2%%date:~-7,2%_%time:~0,2%%time:~3,2%%time:~6,2%
set BACKUP_FILE=%BACKUP_DIR%\csdt_database_%DATE%.sql

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

mysqldump -u csdt -p123 csdt_final > "%BACKUP_FILE%"
powershell -Command "Compress-Archive -Path '%BACKUP_FILE%' -DestinationPath '%BACKUP_FILE%.zip' -Force"
del "%BACKUP_FILE%"

echo %GREEN%[SUCCESS]%NC% ✅ Backup completado: %BACKUP_FILE%.zip
echo.
pause
goto menu

:monitor
cls
echo %BLUE%===========================================%NC%
echo %BLUE%MONITOR EN TIEMPO REAL%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Presiona Ctrl+C para salir del monitor
echo.
pm2 monit
goto menu

:configurar_db
cls
echo %BLUE%===========================================%NC%
echo %BLUE%CONFIGURAR BASE DE DATOS%NC%
echo %BLUE%===========================================%NC%
echo.
call "%~dp0configurar_base_datos.bat"
echo.
pause
goto menu

:instalar_deps
cls
echo %BLUE%===========================================%NC%
echo %BLUE%INSTALAR DEPENDENCIAS%NC%
echo %BLUE%===========================================%NC%
echo.
call "%~dp0instalar_librerias.bat"
echo.
pause
goto menu

:salir
cls
echo %BLUE%===========================================%NC%
echo %BLUE%SALIENDO%NC%
echo %BLUE%===========================================%NC%
echo.
echo %GREEN%[INFO]%NC% Gracias por usar el sistema CSDT
echo %GREEN%[INFO]%NC% Para más información, consulta la documentación
echo.
exit /b 0
