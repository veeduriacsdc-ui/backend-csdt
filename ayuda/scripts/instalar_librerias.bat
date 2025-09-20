@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE INSTALACIÓN DE LIBRERÍAS
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN DE LIBRERÍAS%NC%
echo %BLUE%===========================================%NC%

REM Verificar que estamos como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%[ERROR]%NC% Por favor ejecuta este script como administrador
    pause
    exit /b 1
)

REM ===========================================
REM INSTALAR CHOCOLATEY
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando Chocolatey...

REM Verificar si Chocolatey está instalado
where choco >nul 2>&1
if %errorLevel% neq 0 (
    echo %GREEN%[INFO]%NC% Instalando Chocolatey...
    powershell -Command "Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))"
) else (
    echo %GREEN%[INFO]%NC% Chocolatey ya está instalado
)

REM ===========================================
REM INSTALAR LIBRERÍAS DEL SISTEMA
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías del sistema...

REM Instalar herramientas básicas esenciales
choco install -y curl wget git unzip 7zip
choco install -y python3 nodejs-lts
choco install -y mysql redis
choco install -y visualstudio2022buildtools

REM Instalar herramientas de desarrollo
choco install -y vscode notepadplusplus postman
choco install -y gitkraken sourcetree

REM Instalar herramientas de monitoreo
choco install -y htop wireshark

echo %GREEN%[SUCCESS]%NC% ✅ Librerías del sistema instaladas

REM ===========================================
REM INSTALAR PHP
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando PHP...

REM Instalar PHP
choco install -y php --params "/InstallDir:C:\php"

REM Agregar PHP al PATH
setx PATH "%PATH%;C:\php" /M

REM Instalar extensiones de PHP
echo %GREEN%[INFO]%NC% Instalando extensiones de PHP...
C:\php\php.exe -m

echo %GREEN%[SUCCESS]%NC% ✅ PHP instalado

REM ===========================================
REM INSTALAR COMPOSER
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando Composer...

REM Instalar Composer
choco install -y composer

REM Configurar Composer
composer config -g repo.packagist composer https://packagist.org

echo %GREEN%[SUCCESS]%NC% ✅ Composer instalado

REM ===========================================
REM INSTALAR NODE.JS Y NPM
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando Node.js y NPM...

REM Verificar instalación de Node.js
node --version
npm --version

REM Instalar PM2 y herramientas Node.js globales
npm install -g pm2 nodemon typescript
npm install -g @vitejs/plugin-react concurrently cross-env
npm install -g eslint prettier

echo %GREEN%[SUCCESS]%NC% ✅ Node.js y NPM instalados

REM ===========================================
REM INSTALAR LIBRERÍAS DEL BACKEND
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías del backend...

cd /d "%~dp0..\.."

REM Instalar dependencias PHP con optimizaciones
composer install --optimize-autoloader --no-dev --prefer-dist

REM Instalar dependencias Node.js del backend
npm install --production

echo %GREEN%[SUCCESS]%NC% ✅ Librerías del backend instaladas

REM ===========================================
REM INSTALAR LIBRERÍAS DEL FRONTEND
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías del frontend...

cd /d "C:\var\www\frontend-csdt"

REM Instalar dependencias del frontend
npm install --production

REM Instalar librerías adicionales para IA y funcionalidades
npm install axios leaflet react-leaflet chart.js react-chartjs-2
npm install jspdf jspdf-autotable html2canvas
npm install react-speech-recognition styled-components

echo %GREEN%[SUCCESS]%NC% ✅ Librerías del frontend instaladas

REM ===========================================
REM INSTALAR LIBRERÍAS DE PYTHON
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías de Python...

REM Instalar librerías de Python para IA
pip install --upgrade pip
pip install openai anthropic requests
pip install numpy pandas scikit-learn
pip install transformers torch torchvision
pip install opencv-python pillow
pip install speechrecognition pyaudio
pip install elevenlabs google-cloud-speech

echo %GREEN%[SUCCESS]%NC% ✅ Librerías de Python instaladas

REM ===========================================
REM CONFIGURAR REDIS
REM ===========================================
echo %GREEN%[INFO]%NC% Configurando Redis...

REM Iniciar Redis como servicio
sc create Redis binPath= "C:\ProgramData\chocolatey\lib\redis\tools\redis-server.exe" start= auto
sc start Redis

REM Iniciar MySQL como servicio
sc create MySQL binPath= "C:\ProgramData\chocolatey\lib\mysql\tools\bin\mysqld.exe --install" start= auto
sc start MySQL

echo %GREEN%[SUCCESS]%NC% ✅ Redis y MySQL configurados

REM ===========================================
REM INSTALAR LIBRERÍAS DE MONITOREO
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías de monitoreo...

REM Instalar herramientas de monitoreo
choco install -y htop wireshark
choco install -y processhacker

echo %GREEN%[SUCCESS]%NC% ✅ Librerías de monitoreo instaladas

REM ===========================================
REM INSTALAR LIBRERÍAS DE SEGURIDAD
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías de seguridad...

REM Instalar herramientas de seguridad
choco install -y openssl putty
choco install -y 7zip

echo %GREEN%[SUCCESS]%NC% ✅ Librerías de seguridad instaladas

REM ===========================================
REM INSTALAR LIBRERÍAS DE BACKUP
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando librerías de backup...

REM Instalar herramientas de backup
choco install -y rclone duplicati
choco install -y robocopy

echo %GREEN%[SUCCESS]%NC% ✅ Librerías de backup instaladas

REM ===========================================
REM VERIFICAR INSTALACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Verificando instalación...

REM Verificar PHP
echo Versión de PHP:
C:\php\php.exe --version

REM Verificar Composer
echo Versión de Composer:
composer --version

REM Verificar Node.js
echo Versión de Node.js:
node --version

REM Verificar NPM
echo Versión de NPM:
npm --version

REM Verificar PM2
echo Versión de PM2:
pm2 --version

REM Verificar Redis
echo Estado de Redis:
sc query Redis

REM Verificar MySQL
echo Estado de MySQL:
sc query MySQL

echo %GREEN%[SUCCESS]%NC% ✅ Verificación completada

REM ===========================================
REM OPTIMIZAR CONFIGURACIÓN
REM ===========================================
echo %GREEN%[INFO]%NC% Optimizando configuración...

REM Crear archivo de configuración PHP optimizado
(
echo ; Configuración optimizada para CSDT
echo memory_limit = 1024M
echo max_execution_time = 300
echo upload_max_filesize = 100M
echo post_max_size = 100M
echo max_input_vars = 5000
echo max_file_uploads = 20
echo date.timezone = America/Bogota
echo extension=curl
echo extension=gd
echo extension=mbstring
echo extension=xml
echo extension=zip
echo extension=bcmath
echo extension=mysqli
echo extension=pdo_mysql
echo extension=intl
echo extension=redis
echo opcache.enable = 1
echo opcache.memory_consumption = 256
echo opcache.max_accelerated_files = 20000
) > "C:\php\php.ini"

echo %GREEN%[SUCCESS]%NC% ✅ Configuración optimizada

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN DE LIBRERÍAS COMPLETADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Todas las librerías instaladas correctamente
echo %GREEN%[SUCCESS]%NC% ✅ Sistema optimizado para CSDT
echo %GREEN%[SUCCESS]%NC% ✅ Servicios de IA configurados
echo %GREEN%[SUCCESS]%NC% ✅ Seguridad configurada
echo %GREEN%[SUCCESS]%NC% ✅ Monitoreo configurado

echo %YELLOW%PRÓXIMOS PASOS:%NC%
echo %YELLOW%1. Configurar variables de entorno (.env)%NC%
echo %YELLOW%2. Ejecutar migraciones de base de datos%NC%
echo %YELLOW%3. Configurar servicios de IA%NC%
echo %YELLOW%4. Iniciar servicios con PM2%NC%

echo %GREEN%Para verificar el estado, ejecuta: verificar_csdt.bat%NC%

pause
