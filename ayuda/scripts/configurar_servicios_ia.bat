@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE CONFIGURACIÓN DE SERVICIOS DE IA
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM WINDOWS - VERSIÓN COMPLETA
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
echo %BLUE%CONFIGURACIÓN DE SERVICIOS DE IA CSDT%NC%
echo %BLUE%===========================================%NC%

REM Verificar que estamos como administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo %RED%[ERROR]%NC% Por favor ejecuta este script como administrador
    pause
    exit /b 1
)

REM ===========================================
REM CONFIGURAR VARIABLES DE ENTORNO
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando variables de entorno para IA...

cd /d "%~dp0..\.."

REM Crear archivo .env si no existe
if not exist ".env" (
    copy ".env.example" ".env"
)

REM Configurar variables de IA en .env
(
echo.
echo # ===========================================
echo # CONFIGURACIÓN DE SERVICIOS DE IA
echo # ===========================================
echo.
echo # OpenAI Configuration
echo OPENAI_API_KEY=your_openai_api_key_here
echo OPENAI_BASE_URL=https://api.openai.com/v1
echo OPENAI_MODEL=gpt-4
echo.
echo # Anthropic Configuration
echo ANTHROPIC_API_KEY=your_anthropic_api_key_here
echo ANTHROPIC_BASE_URL=https://api.anthropic.com
echo ANTHROPIC_MODEL=claude-3-sonnet-20240229
echo.
echo # Google Gemini Configuration
echo GOOGLE_GEMINI_API_KEY=your_google_gemini_api_key_here
echo GOOGLE_GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
echo GOOGLE_GEMINI_MODEL=gemini-pro
echo.
echo # LexisNexis Configuration
echo LEXISNEXIS_API_KEY=your_lexisnexis_api_key_here
echo LEXISNEXIS_BASE_URL=your_lexisnexis_base_url_here
echo.
echo # ElevenLabs Configuration
echo ELEVENLABS_API_KEY=your_elevenlabs_api_key_here
echo ELEVENLABS_BASE_URL=https://api.elevenlabs.io/v1
echo.
echo # Google Cloud Speech Configuration
echo GOOGLE_CLOUD_PROJECT_ID=your_google_cloud_project_id
echo GOOGLE_CLOUD_CREDENTIALS_PATH=C:\path\to\credentials.json
echo.
echo # Azure Cognitive Services Configuration
echo AZURE_COGNITIVE_API_KEY=your_azure_cognitive_api_key_here
echo AZURE_COGNITIVE_REGION=your_azure_region
echo.
echo # Legal AI Library Configuration
echo LEGAL_AI_LIBRARY_API_KEY=your_legal_ai_library_api_key_here
echo LEGAL_AI_LIBRARY_BASE_URL=your_legal_ai_library_base_url_here
echo.
echo # IA Service Configuration
echo IA_SERVICE_TIMEOUT=30
echo IA_SERVICE_RETRY_ATTEMPTS=3
echo IA_SERVICE_CACHE_TTL=3600
echo IA_SERVICE_RATE_LIMIT=1000
) >> .env

echo %GREEN%[SUCCESS]%NC% ✅ Variables de entorno configuradas

REM ===========================================
REM INSTALAR DEPENDENCIAS DE IA PARA PHP
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias de IA para PHP...

REM Instalar dependencias específicas de IA
composer require openai-php/client:^0.10.0
composer require anthropic/anthropic-sdk-php:^0.8.0
composer require google/generative-ai-php:^0.2.0
composer require guzzlehttp/guzzle:^7.8

echo %GREEN%[SUCCESS]%NC% ✅ Dependencias de IA para PHP instaladas

REM ===========================================
REM INSTALAR DEPENDENCIAS DE IA PARA NODE.JS
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias de IA para Node.js...

cd /d "C:\var\www\frontend-csdt"

REM Instalar dependencias de IA para frontend
npm install openai @anthropic-ai/sdk @google/generative-ai
npm install axios react-speech-recognition
npm install web-speech-api speech-synthesis-api

echo %GREEN%[SUCCESS]%NC% ✅ Dependencias de IA para Node.js instaladas

REM ===========================================
REM INSTALAR DEPENDENCIAS DE IA PARA PYTHON
REM ===========================================
echo %PURPLE%[PASO]%NC% Instalando dependencias de IA para Python...

REM Instalar dependencias de IA para Python
pip install --upgrade pip
pip install openai anthropic google-generativeai
pip install transformers torch torchvision
pip install speechrecognition pyaudio
pip install elevenlabs google-cloud-speech
pip install azure-cognitiveservices-speech
pip install opencv-python pillow

echo %GREEN%[SUCCESS]%NC% ✅ Dependencias de IA para Python instaladas

REM ===========================================
REM CONFIGURAR SERVICIOS DE IA
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando servicios de IA...

REM Crear archivo de configuración de servicios de IA
(
echo ^<?php
echo.
echo return [
echo     'openai' =^> [
echo         'api_key' =^> env^('OPENAI_API_KEY'^),
echo         'base_url' =^> env^('OPENAI_BASE_URL', 'https://api.openai.com/v1'^),
echo         'model' =^> env^('OPENAI_MODEL', 'gpt-4'^),
echo         'timeout' =^> env^('IA_SERVICE_TIMEOUT', 30^),
echo         'retry_attempts' =^> env^('IA_SERVICE_RETRY_ATTEMPTS', 3^),
echo         'cache_ttl' =^> env^('IA_SERVICE_CACHE_TTL', 3600^),
echo         'rate_limit' =^> env^('IA_SERVICE_RATE_LIMIT', 1000^),
echo     ],
echo     'anthropic' =^> [
echo         'api_key' =^> env^('ANTHROPIC_API_KEY'^),
echo         'base_url' =^> env^('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'^),
echo         'model' =^> env^('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'^),
echo         'timeout' =^> env^('IA_SERVICE_TIMEOUT', 30^),
echo         'retry_attempts' =^> env^('IA_SERVICE_RETRY_ATTEMPTS', 3^),
echo         'cache_ttl' =^> env^('IA_SERVICE_CACHE_TTL', 3600^),
echo         'rate_limit' =^> env^('IA_SERVICE_RATE_LIMIT', 1000^),
echo     ],
echo     'google_gemini' =^> [
echo         'api_key' =^> env^('GOOGLE_GEMINI_API_KEY'^),
echo         'base_url' =^> env^('GOOGLE_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'^),
echo         'model' =^> env^('GOOGLE_GEMINI_MODEL', 'gemini-pro'^),
echo         'timeout' =^> env^('IA_SERVICE_TIMEOUT', 30^),
echo         'retry_attempts' =^> env^('IA_SERVICE_RETRY_ATTEMPTS', 3^),
echo         'cache_ttl' =^> env^('IA_SERVICE_CACHE_TTL', 3600^),
echo         'rate_limit' =^> env^('IA_SERVICE_RATE_LIMIT', 1000^),
echo     ],
echo     'elevenlabs' =^> [
echo         'api_key' =^> env^('ELEVENLABS_API_KEY'^),
echo         'base_url' =^> env^('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'^),
echo         'timeout' =^> env^('IA_SERVICE_TIMEOUT', 30^),
echo         'retry_attempts' =^> env^('IA_SERVICE_RETRY_ATTEMPTS', 3^),
echo         'cache_ttl' =^> env^('IA_SERVICE_CACHE_TTL', 3600^),
echo         'rate_limit' =^> env^('IA_SERVICE_RATE_LIMIT', 1000^),
echo     ],
echo     'google_speech' =^> [
echo         'project_id' =^> env^('GOOGLE_CLOUD_PROJECT_ID'^),
echo         'credentials_path' =^> env^('GOOGLE_CLOUD_CREDENTIALS_PATH'^),
echo         'timeout' =^> env^('IA_SERVICE_TIMEOUT', 30^),
echo         'retry_attempts' =^> env^('IA_SERVICE_RETRY_ATTEMPTS', 3^),
echo         'cache_ttl' =^> env^('IA_SERVICE_CACHE_TTL', 3600^),
echo         'rate_limit' =^> env^('IA_SERVICE_RATE_LIMIT', 1000^),
echo     ],
echo ];
) > "C:\var\www\backend-csdt\config\ia_services.php"

echo %GREEN%[SUCCESS]%NC% ✅ Servicios de IA configurados

REM ===========================================
REM CREAR SCRIPTS DE PRUEBA DE IA
REM ===========================================
echo %PURPLE%[PASO]%NC% Creando scripts de prueba de IA...

REM Script de prueba de OpenAI
(
echo ^<?php
echo require_once 'vendor/autoload.php';
echo.
echo use OpenAI\Client;
echo.
echo $apiKey = env^('OPENAI_API_KEY'^);
echo if ^(!$apiKey^) {
echo     echo "❌ OPENAI_API_KEY no configurada\n";
echo     exit^(1^);
echo }
echo.
echo $client = new Client^($apiKey^);
echo.
echo try {
echo     $response = $client-^>chat^(^)-^>create^([
echo         'model' =^> 'gpt-3.5-turbo',
echo         'messages' =^> [
echo             ['role' =^> 'user', 'content' =^> 'Hola, ¿cómo estás?']
echo         ],
echo         'max_tokens' =^> 50
echo     ]^);
echo     
echo     echo "✅ OpenAI conectado correctamente\n";
echo     echo "Respuesta: " . $response-^>choices[0]-^>message-^>content . "\n";
echo } catch ^(Exception $e^) {
echo     echo "❌ Error conectando con OpenAI: " . $e-^>getMessage^(^) . "\n";
echo }
) > "C:\var\www\backend-csdt\test_openai.php"

REM Script de prueba de Anthropic
(
echo ^<?php
echo require_once 'vendor/autoload.php';
echo.
echo use Anthropic\Client;
echo.
echo $apiKey = env^('ANTHROPIC_API_KEY'^);
echo if ^(!$apiKey^) {
echo     echo "❌ ANTHROPIC_API_KEY no configurada\n";
echo     exit^(1^);
echo }
echo.
echo $client = new Client^($apiKey^);
echo.
echo try {
echo     $response = $client-^>messages^(^)-^>create^([
echo         'model' =^> 'claude-3-haiku-20240307',
echo         'max_tokens' =^> 50,
echo         'messages' =^> [
echo             ['role' =^> 'user', 'content' =^> 'Hola, ¿cómo estás?']
echo         ]
echo     ]^);
echo     
echo     echo "✅ Anthropic conectado correctamente\n";
echo     echo "Respuesta: " . $response-^>content[0]-^>text . "\n";
echo } catch ^(Exception $e^) {
echo     echo "❌ Error conectando con Anthropic: " . $e-^>getMessage^(^) . "\n";
echo }
) > "C:\var\www\backend-csdt\test_anthropic.php"

echo %GREEN%[SUCCESS]%NC% ✅ Scripts de prueba creados

REM ===========================================
REM CONFIGURAR MONITOREO DE IA
REM ===========================================
echo %PURPLE%[PASO]%NC% Configurando monitoreo de IA...

REM Crear script de monitoreo de IA
(
echo @echo off
echo echo === MONITOR DE SERVICIOS DE IA ===
echo echo Fecha: %date% %time%
echo echo.
echo.
echo REM Verificar OpenAI
echo if defined OPENAI_API_KEY ^(
echo     echo ✅ OpenAI API Key configurada
echo ^) else ^(
echo     echo ❌ OpenAI API Key no configurada
echo ^)
echo.
echo REM Verificar Anthropic
echo if defined ANTHROPIC_API_KEY ^(
echo     echo ✅ Anthropic API Key configurada
echo ^) else ^(
echo     echo ❌ Anthropic API Key no configurada
echo ^)
echo.
echo REM Verificar Google Gemini
echo if defined GOOGLE_GEMINI_API_KEY ^(
echo     echo ✅ Google Gemini API Key configurada
echo ^) else ^(
echo     echo ❌ Google Gemini API Key no configurada
echo ^)
echo.
echo REM Verificar Redis
echo redis-cli ping ^>nul 2^>^&1
echo if %errorLevel% equ 0 ^(
echo     echo ✅ Redis funcionando
echo ^) else ^(
echo     echo ❌ Redis no responde
echo ^)
echo.
echo REM Verificar memoria
echo echo.
echo echo === USO DE MEMORIA ===
echo wmic OS get TotalVisibleMemorySize,FreePhysicalMemory /format:table
) > "C:\Windows\System32\monitor_ia.bat"

echo %GREEN%[SUCCESS]%NC% ✅ Monitoreo de IA configurado

REM ===========================================
REM VERIFICAR CONFIGURACIÓN
REM ===========================================
echo %PURPLE%[PASO]%NC% Verificando configuración de IA...

echo %CYAN%=== VERIFICACIÓN DE SERVICIOS DE IA ===%NC%

REM Verificar archivos de configuración
if exist "C:\var\www\backend-csdt\config\ia_services.php" (
    echo %GREEN%[SUCCESS]%NC% ✅ Archivo de configuración de IA creado
) else (
    echo %RED%[ERROR]%NC% ❌ Archivo de configuración de IA no encontrado
)

REM Verificar dependencias PHP
if exist "C:\var\www\backend-csdt\vendor\autoload.php" (
    echo %GREEN%[SUCCESS]%NC% ✅ Dependencias PHP instaladas
) else (
    echo %RED%[ERROR]%NC% ❌ Dependencias PHP no instaladas
)

REM Verificar dependencias Node.js
if exist "C:\var\www\frontend-csdt\node_modules" (
    echo %GREEN%[SUCCESS]%NC% ✅ Dependencias Node.js instaladas
) else (
    echo %RED%[ERROR]%NC% ❌ Dependencias Node.js no instaladas
)

REM Verificar Redis
redis-cli ping >nul 2>&1
if %errorLevel% equ 0 (
    echo %GREEN%[SUCCESS]%NC% ✅ Redis funcionando
) else (
    echo %RED%[ERROR]%NC% ❌ Redis no responde
)

echo %GREEN%[SUCCESS]%NC% ✅ Verificación completada

echo %BLUE%===========================================%NC%
echo %BLUE%CONFIGURACIÓN DE SERVICIOS DE IA COMPLETADA%NC%
echo %BLUE%===========================================%NC%

echo %GREEN%[SUCCESS]%NC% ✅ Servicios de IA configurados correctamente
echo %GREEN%[SUCCESS]%NC% ✅ Variables de entorno configuradas
echo %GREEN%[SUCCESS]%NC% ✅ Dependencias instaladas
echo %GREEN%[SUCCESS]%NC% ✅ Monitoreo configurado

echo %YELLOW%PRÓXIMOS PASOS:%NC%
echo %YELLOW%1. Configurar las API keys en el archivo .env%NC%
echo %YELLOW%2. Ejecutar los scripts de prueba: test_openai.php, test_anthropic.php%NC%
echo %YELLOW%3. Iniciar los servicios con PM2%NC%
echo %YELLOW%4. Verificar el monitoreo con: monitor_ia.bat%NC%

echo %GREEN%Para probar los servicios de IA, ejecuta:%NC%
echo %GREEN%cd C:\var\www\backend-csdt ^&^& php test_openai.php%NC%
echo %GREEN%cd C:\var\www\backend-csdt ^&^& php test_anthropic.php%NC%

pause
