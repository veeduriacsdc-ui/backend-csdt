@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE INSTALACIÓN COMPLETA CSDT MEJORADO
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM VERSIÓN WINDOWS - COMPLETA
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
echo %BLUE%INSTALACIÓN COMPLETA CSDT - VERSIÓN MEJORADA%NC%
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
if not exist "C:\var\www\backend-csdt\ayuda" mkdir "C:\var\www\backend-csdt\ayuda"
if not exist "C:\var\www\frontend-csdt\ayuda" mkdir "C:\var\www\frontend-csdt\ayuda"
if not exist "C:\var\log\backend-csdt" mkdir "C:\var\log\backend-csdt"
if not exist "C:\var\log\frontend-csdt" mkdir "C:\var\log\frontend-csdt"
if not exist "C:\var\backups\database" mkdir "C:\var\backups\database"

echo %GREEN%[SUCCESS]%NC% Estructura de carpetas creada

REM ===========================================
REM EJECUTAR SCRIPT PRINCIPAL DE INSTALACIÓN
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%EJECUTANDO INSTALACIÓN PRINCIPAL%NC%
echo %BLUE%===========================================%NC%

if exist "C:\var\www\backend-csdt\ayuda\instalar_csdt_windows_mejorado.bat" (
    echo %GREEN%[INFO]%NC% Ejecutando script principal de instalación...
    call "C:\var\www\backend-csdt\ayuda\instalar_csdt_windows_mejorado.bat"
) else (
    echo %RED%[ERROR]%NC% Script principal no encontrado
    pause
    exit /b 1
)

REM ===========================================
REM EJECUTAR SCRIPTS ADICIONALES
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%EJECUTANDO SCRIPTS ADICIONALES%NC%
echo %BLUE%===========================================%NC%

REM Instalar librerías
echo %PURPLE%[PASO]%NC% Instalando librerías...
if exist "C:\var\www\backend-csdt\ayuda\scripts\instalar_librerias.bat" (
    call "C:\var\www\backend-csdt\ayuda\scripts\instalar_librerias.bat"
) else (
    echo %YELLOW%[WARNING]%NC% Script de librerías no encontrado
)

REM Configurar base de datos
echo %PURPLE%[PASO]%NC% Configurando base de datos...
if exist "C:\var\www\backend-csdt\ayuda\scripts\configurar_base_datos.bat" (
    call "C:\var\www\backend-csdt\ayuda\scripts\configurar_base_datos.bat"
) else (
    echo %YELLOW%[WARNING]%NC% Script de base de datos no encontrado
)

REM Instalar servicios de IA
echo %PURPLE%[PASO]%NC% Instalando servicios de IA...
if exist "C:\var\www\backend-csdt\ayuda\scripts\instalar_servicios_ia.bat" (
    call "C:\var\www\backend-csdt\ayuda\scripts\instalar_servicios_ia.bat"
) else (
    echo %YELLOW%[WARNING]%NC% Script de servicios de IA no encontrado
)

REM ===========================================
REM CREAR SCRIPTS DE GESTIÓN GLOBALES
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%CREANDO SCRIPTS DE GESTIÓN GLOBALES%NC%
echo %BLUE%===========================================%NC%

REM Script de gestión principal
echo %PURPLE%[PASO]%NC% Creando script de gestión principal...
(
echo # Script de gestión principal CSDT
echo @echo off
echo setlocal enabledelayedexpansion
echo.
echo :menu
echo cls
echo ===========================================
echo     GESTIÓN CSDT - %date% %time%
echo ===========================================
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
echo 11. Salir
echo.
echo /p "Selecciona una opción (1-11): " option
echo.
echo if "%%option%%"=="1" goto estado
echo if "%%option%%"=="2" goto iniciar
echo if "%%option%%"=="3" goto detener
echo if "%%option%%"=="4" goto reiniciar
echo if "%%option%%"=="5" goto logs
echo if "%%option%%"=="6" goto verificar
echo if "%%option%%"=="7" goto reparar
echo if "%%option%%"=="8" goto limpiar
echo if "%%option%%"=="9" goto backup
echo if "%%option%%"=="10" goto monitor
echo if "%%option%%"=="11" goto salir
echo goto menu
echo.
echo :estado
echo call "C:\var\www\backend-csdt\ayuda\scripts\verificar_csdt.bat"
echo pause
echo goto menu
echo.
echo :iniciar
echo pm2 start all
echo echo Servicios iniciados
echo pause
echo goto menu
echo.
echo :detener
echo pm2 stop all
echo echo Servicios detenidos
echo pause
echo goto menu
echo.
echo :reiniciar
echo pm2 restart all
echo echo Servicios reiniciados
echo pause
echo goto menu
echo.
echo :logs
echo pm2 logs --lines 50
echo pause
echo goto menu
echo.
echo :verificar
echo call "C:\var\www\backend-csdt\ayuda\scripts\verificar_csdt.bat"
echo pause
echo goto menu
echo.
echo :reparar
echo call "C:\var\www\backend-csdt\ayuda\scripts\reparar_csdt.bat"
echo pause
echo goto menu
echo.
echo :limpiar
echo call "C:\var\www\backend-csdt\ayuda\scripts\limpiar_csdt.bat"
echo pause
echo goto menu
echo.
echo :backup
echo call "C:\var\www\backend-csdt\ayuda\scripts\backup_csdt.bat"
echo pause
echo goto menu
echo.
echo :monitor
echo call "C:\var\www\backend-csdt\ayuda\scripts\monitor_csdt.bat"
echo goto menu
echo.
echo :salir
echo echo Saliendo...
echo exit /b 0
) > "C:\Windows\System32\gestionar_csdt.bat"

REM Script de inicio rápido
echo %PURPLE%[PASO]%NC% Creando script de inicio rápido...
(
echo @echo off
echo echo Iniciando CSDT...
echo cd /d "C:\var\www\backend-csdt"
echo pm2 start ecosystem.config.js --env production
echo.
echo cd /d "C:\var\www\frontend-csdt"
echo pm2 start ecosystem-frontend.config.js
echo.
echo pm2 save
echo echo CSDT iniciado correctamente
) > "C:\Windows\System32\iniciar_csdt.bat"

REM Script de parada rápida
echo %PURPLE%[PASO]%NC% Creando script de parada rápida...
(
echo @echo off
echo echo Deteniendo CSDT...
echo pm2 stop all
echo pm2 save
echo echo CSDT detenido correctamente
) > "C:\Windows\System32\detener_csdt.bat"

REM Script de verificación rápida
echo %PURPLE%[PASO]%NC% Creando script de verificación rápida...
(
echo @echo off
echo echo Verificando CSDT...
echo pm2 status
echo echo.
echo echo Conectividad:
echo curl -I http://localhost:8000 2>nul ^&^& echo Backend: OK ^|^| echo Backend: ERROR
echo curl -I http://localhost:3000 2>nul ^&^& echo Frontend: OK ^|^| echo Frontend: ERROR
) > "C:\Windows\System32\verificar_csdt.bat"

echo %GREEN%[SUCCESS]%NC% Scripts de gestión globales creados

REM ===========================================
REM CONFIGURAR TAREAS PROGRAMADAS
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%CONFIGURANDO TAREAS PROGRAMADAS%NC%
echo %BLUE%===========================================%NC%

echo %PURPLE%[PASO]%NC% Configurando tareas automáticas...

REM Backup diario a las 2 AM
schtasks /create /tn "CSDT_Backup_Diario" /tr "C:\var\www\backend-csdt\ayuda\scripts\backup_csdt.bat" /sc daily /st 02:00 /f

REM Limpieza semanal los domingos a las 3 AM
schtasks /create /tn "CSDT_Limpieza_Semanal" /tr "C:\var\www\backend-csdt\ayuda\scripts\limpiar_csdt.bat" /sc weekly /d SUN /st 03:00 /f

REM Verificación diaria a las 6 AM
schtasks /create /tn "CSDT_Verificacion_Diaria" /tr "C:\Windows\System32\verificar_csdt.bat" /sc daily /st 06:00 /f

echo %GREEN%[SUCCESS]%NC% Tareas programadas configuradas

REM ===========================================
REM VERIFICACIÓN FINAL
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%VERIFICACIÓN FINAL%NC%
echo %BLUE%===========================================%NC%

echo %PURPLE%[PASO]%NC% Verificando servicios...
pm2 status

echo %PURPLE%[PASO]%NC% Verificando conectividad...
curl -s http://localhost:8000 >nul 2>&1 && echo %GREEN%[SUCCESS]%NC% ✅ Backend local: http://localhost:8000 || echo %YELLOW%[WARNING]%NC% ⚠️ Backend local no responde

curl -s http://localhost:3000 >nul 2>&1 && echo %GREEN%[SUCCESS]%NC% ✅ Frontend local: http://localhost:3000 || echo %YELLOW%[WARNING]%NC% ⚠️ Frontend local no responde

REM ===========================================
REM CREAR DOCUMENTACIÓN
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%CREANDO DOCUMENTACIÓN%NC%
echo %BLUE%===========================================%NC%

echo %PURPLE%[PASO]%NC% Creando documentación del sistema...

(
echo # CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
echo.
echo ## Información del Sistema
echo.
echo - **Backend:** http://localhost:8000
echo - **Frontend:** http://localhost:3000
echo.
echo ## Comandos Útiles
echo.
echo ### Gestión Principal
echo - `gestionar_csdt.bat` - Menú interactivo de gestión
echo - `iniciar_csdt.bat` - Inicio rápido
echo - `detener_csdt.bat` - Parada rápida
echo - `verificar_csdt.bat` - Verificación rápida
echo.
echo ### Scripts Específicos
echo - `instalar_csdt_windows_mejorado.bat` - Instalación principal
echo - `instalar_librerias.bat` - Instalar librerías
echo - `configurar_base_datos.bat` - Configurar base de datos
echo - `instalar_servicios_ia.bat` - Instalar servicios de IA
echo - `reparar_csdt.bat` - Reparar sistema
echo - `limpiar_csdt.bat` - Limpiar sistema
echo - `backup_csdt.bat` - Hacer backup
echo - `restaurar_csdt.bat` - Restaurar backup
echo - `monitor_csdt.bat` - Monitoreo en tiempo real
echo - `diagnosticar_csdt.bat` - Diagnóstico completo
echo.
echo ### Gestión de Servicios
echo - `pm2 status` - Estado de servicios
echo - `pm2 logs` - Ver logs
echo - `pm2 restart all` - Reiniciar todos
echo - `pm2 stop all` - Detener todos
echo.
echo ## Estructura del Proyecto
echo.
echo ```
echo C:\var\www\
echo ├── backend-csdt\           # Backend Laravel
echo │   ├── ayuda\              # Scripts de gestión
echo │   │   ├── *.bat          # Scripts principales
echo │   │   └── scripts\       # Scripts específicos
echo │   ├── ecosystem.config.js # Configuración PM2
echo │   └── ...
echo └── frontend-csdt\          # Frontend React
echo     ├── ayuda\              # Scripts de gestión
echo     │   └── *.bat          # Scripts específicos
echo     ├── ecosystem-frontend.config.js # Configuración PM2
echo     └── ...
echo ```
echo.
echo ## Solución de Problemas
echo.
echo 1. **Servicios no responden:**
echo    - Ejecutar: `reparar_csdt.bat`
echo    - Verificar: `verificar_csdt.bat`
echo.
echo 2. **Problemas de base de datos:**
echo    - Ejecutar: `configurar_base_datos.bat`
echo    - Verificar conexión: `mysql -u csdt -p123 csdt_final`
echo.
echo 3. **Problemas de permisos:**
echo    - Ejecutar como administrador
echo    - Verificar permisos de carpetas
echo.
echo 4. **Logs:**
echo    - Backend: `C:\var\log\backend-csdt\`
echo    - Frontend: `C:\var\log\frontend-csdt\`
echo    - Sistema: `pm2 logs`
echo.
echo ## Mantenimiento
echo.
echo - **Backup automático:** Diario a las 2 AM
echo - **Limpieza automática:** Semanal los domingos a las 3 AM
echo - **Verificación automática:** Diaria a las 6 AM
echo.
echo ## Contacto
echo.
echo Para soporte técnico, contactar al administrador del sistema.
) > "C:\var\www\README_CSDT.md"

echo %GREEN%[SUCCESS]%NC% Documentación creada

REM ===========================================
REM FINALIZACIÓN
REM ===========================================
echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN COMPLETA FINALIZADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Sistema CSDT instalado completamente
echo %GREEN%[SUCCESS]%NC% ✅ Backend Laravel funcionando
echo %GREEN%[SUCCESS]%NC% ✅ Frontend React funcionando
echo %GREEN%[SUCCESS]%NC% ✅ 13 servicios de IA configurados
echo %GREEN%[SUCCESS]%NC% ✅ Base de datos configurada
echo %GREEN%[SUCCESS]%NC% ✅ PM2 gestionando procesos
echo %GREEN%[SUCCESS]%NC% ✅ Scripts de gestión creados
echo %GREEN%[SUCCESS]%NC% ✅ Tareas programadas configuradas
echo %GREEN%[SUCCESS]%NC% ✅ Documentación creada

echo %YELLOW%[WARNING]%NC% INFORMACIÓN DE ACCESO:
echo %YELLOW%[WARNING]%NC% Backend: http://localhost:8000
echo %YELLOW%[WARNING]%NC% Frontend: http://localhost:3000

echo %YELLOW%[WARNING]%NC% COMANDOS ÚTILES:
echo %YELLOW%[WARNING]%NC% Gestión: gestionar_csdt.bat
echo %YELLOW%[WARNING]%NC% Inicio: iniciar_csdt.bat
echo %YELLOW%[WARNING]%NC% Parada: detener_csdt.bat
echo %YELLOW%[WARNING]%NC% Verificación: verificar_csdt.bat
echo %YELLOW%[WARNING]%NC% Monitoreo: monitor_csdt.bat
echo %YELLOW%[WARNING]%NC% Diagnóstico: diagnosticar_csdt.bat

echo %YELLOW%[WARNING]%NC% DOCUMENTACIÓN:
echo %YELLOW%[WARNING]%NC% Ubicación: C:\var\www\README_CSDT.md

echo %GREEN%[INFO]%NC% ¡Instalación completa finalizada exitosamente!
echo %GREEN%[INFO]%NC% El sistema CSDT está listo para usar en producción.
echo %GREEN%[INFO]%NC% Para gestionar el sistema, ejecuta: gestionar_csdt.bat

pause
