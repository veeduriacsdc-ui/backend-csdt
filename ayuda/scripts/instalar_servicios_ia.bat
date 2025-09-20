@echo off
setlocal enabledelayedexpansion

REM ===========================================
REM SCRIPT DE INSTALACIÓN DE SERVICIOS DE IA
REM CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
REM ===========================================

REM Colores para output
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN DE SERVICIOS DE IA%NC%
echo %BLUE%===========================================%NC%

REM ===========================================
REM INSTALAR SERVICIOS DE IA EN BACKEND
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando servicios de IA en backend...

cd /d "%~dp0..\.."

REM Instalar dependencias de IA para PHP
composer require openai-php/client
composer require anthropic/anthropic-sdk-php
composer require guzzlehttp/guzzle

echo %GREEN%[SUCCESS]%NC% ✅ Servicios de IA del backend instalados

REM ===========================================
REM INSTALAR SERVICIOS DE IA EN FRONTEND
REM ===========================================
echo %GREEN%[INFO]%NC% Instalando servicios de IA en frontend...

cd /d "C:\var\www\frontend-csdt"

REM Instalar dependencias de IA para React
npm install openai
npm install @anthropic-ai/sdk
npm install axios

echo %GREEN%[SUCCESS]%NC% ✅ Servicios de IA del frontend instalados

echo %BLUE%===========================================%NC%
echo %BLUE%INSTALACIÓN DE SERVICIOS DE IA COMPLETADA%NC%
echo %BLUE%===========================================%NC%

pause
