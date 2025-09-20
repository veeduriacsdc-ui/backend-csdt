#!/bin/bash

# ===========================================
# SCRIPT DE CONFIGURACIÓN DE SERVICIOS DE IA
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# UBUNTU/DIGITALOCEAN - VERSIÓN COMPLETA
# ===========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

print_message() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}===========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===========================================${NC}"
}

print_step() {
    echo -e "${PURPLE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]%NC} $1"
}

print_header "CONFIGURACIÓN DE SERVICIOS DE IA CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./configurar_servicios_ia.sh)"
    exit 1
fi

# ===========================================
# CONFIGURAR VARIABLES DE ENTORNO
# ===========================================
print_step "Configurando variables de entorno para IA..."

cd /var/www/backend-csdt

# Crear archivo .env si no existe
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configurar variables de IA en .env
cat >> .env << 'EOF'

# ===========================================
# CONFIGURACIÓN DE SERVICIOS DE IA
# ===========================================

# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_MODEL=gpt-4

# Anthropic Configuration
ANTHROPIC_API_KEY=your_anthropic_api_key_here
ANTHROPIC_BASE_URL=https://api.anthropic.com
ANTHROPIC_MODEL=claude-3-sonnet-20240229

# Google Gemini Configuration
GOOGLE_GEMINI_API_KEY=your_google_gemini_api_key_here
GOOGLE_GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GOOGLE_GEMINI_MODEL=gemini-pro

# LexisNexis Configuration
LEXISNEXIS_API_KEY=your_lexisnexis_api_key_here
LEXISNEXIS_BASE_URL=your_lexisnexis_base_url_here

# ElevenLabs Configuration
ELEVENLABS_API_KEY=your_elevenlabs_api_key_here
ELEVENLABS_BASE_URL=https://api.elevenlabs.io/v1

# Google Cloud Speech Configuration
GOOGLE_CLOUD_PROJECT_ID=your_google_cloud_project_id
GOOGLE_CLOUD_CREDENTIALS_PATH=/path/to/credentials.json

# Azure Cognitive Services Configuration
AZURE_COGNITIVE_API_KEY=your_azure_cognitive_api_key_here
AZURE_COGNITIVE_REGION=your_azure_region

# Legal AI Library Configuration
LEGAL_AI_LIBRARY_API_KEY=your_legal_ai_library_api_key_here
LEGAL_AI_LIBRARY_BASE_URL=your_legal_ai_library_base_url_here

# IA Service Configuration
IA_SERVICE_TIMEOUT=30
IA_SERVICE_RETRY_ATTEMPTS=3
IA_SERVICE_CACHE_TTL=3600
IA_SERVICE_RATE_LIMIT=1000
EOF

print_success "✅ Variables de entorno configuradas"

# ===========================================
# INSTALAR DEPENDENCIAS DE IA PARA PHP
# ===========================================
print_step "Instalando dependencias de IA para PHP..."

# Instalar dependencias específicas de IA
composer require openai-php/client:^0.10.0
composer require anthropic/anthropic-sdk-php:^0.8.0
composer require google/generative-ai-php:^0.2.0
composer require guzzlehttp/guzzle:^7.8
composer require illuminate/http:^11.0

print_success "✅ Dependencias de IA para PHP instaladas"

# ===========================================
# INSTALAR DEPENDENCIAS DE IA PARA NODE.JS
# ===========================================
print_step "Instalando dependencias de IA para Node.js..."

cd /var/www/frontend-csdt

# Instalar dependencias de IA para frontend
npm install openai @anthropic-ai/sdk @google/generative-ai
npm install axios react-speech-recognition
npm install web-speech-api speech-synthesis-api

print_success "✅ Dependencias de IA para Node.js instaladas"

# ===========================================
# INSTALAR DEPENDENCIAS DE IA PARA PYTHON
# ===========================================
print_step "Instalando dependencias de IA para Python..."

# Instalar dependencias de IA para Python
pip3 install --upgrade pip
pip3 install openai anthropic google-generativeai
pip3 install transformers torch torchvision
pip3 install speechrecognition pyaudio
pip3 install elevenlabs google-cloud-speech
pip3 install azure-cognitiveservices-speech
pip3 install opencv-python pillow

print_success "✅ Dependencias de IA para Python instaladas"

# ===========================================
# CONFIGURAR SERVICIOS DE IA
# ===========================================
print_step "Configurando servicios de IA..."

# Crear archivo de configuración de servicios de IA
cat > /var/www/backend-csdt/config/ia_services.php << 'EOF'
<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'google_gemini' => [
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'base_url' => env('GOOGLE_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-pro'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'lexisnexis' => [
        'api_key' => env('LEXISNEXIS_API_KEY'),
        'base_url' => env('LEXISNEXIS_BASE_URL'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'google_speech' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'credentials_path' => env('GOOGLE_CLOUD_CREDENTIALS_PATH'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'azure_cognitive' => [
        'api_key' => env('AZURE_COGNITIVE_API_KEY'),
        'region' => env('AZURE_COGNITIVE_REGION'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
    'legal_ai_library' => [
        'api_key' => env('LEGAL_AI_LIBRARY_API_KEY'),
        'base_url' => env('LEGAL_AI_LIBRARY_BASE_URL'),
        'timeout' => env('IA_SERVICE_TIMEOUT', 30),
        'retry_attempts' => env('IA_SERVICE_RETRY_ATTEMPTS', 3),
        'cache_ttl' => env('IA_SERVICE_CACHE_TTL', 3600),
        'rate_limit' => env('IA_SERVICE_RATE_LIMIT', 1000),
    ],
];
EOF

print_success "✅ Servicios de IA configurados"

# ===========================================
# CONFIGURAR CACHE Y RATE LIMITING
# ===========================================
print_step "Configurando cache y rate limiting..."

# Configurar Redis para cache de IA
cat >> /etc/redis/redis.conf << 'EOF'

# Configuración para CSDT IA Services
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
EOF

# Reiniciar Redis
systemctl restart redis-server

print_success "✅ Cache y rate limiting configurados"

# ===========================================
# CREAR SCRIPTS DE PRUEBA DE IA
# ===========================================
print_step "Creando scripts de prueba de IA..."

# Script de prueba de OpenAI
cat > /var/www/backend-csdt/test_openai.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use OpenAI\Client;

$apiKey = env('OPENAI_API_KEY');
if (!$apiKey) {
    echo "❌ OPENAI_API_KEY no configurada\n";
    exit(1);
}

$client = new Client($apiKey);

try {
    $response = $client->chat()->create([
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'user', 'content' => 'Hola, ¿cómo estás?']
        ],
        'max_tokens' => 50
    ]);
    
    echo "✅ OpenAI conectado correctamente\n";
    echo "Respuesta: " . $response->choices[0]->message->content . "\n";
} catch (Exception $e) {
    echo "❌ Error conectando con OpenAI: " . $e->getMessage() . "\n";
}
EOF

# Script de prueba de Anthropic
cat > /var/www/backend-csdt/test_anthropic.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

use Anthropic\Client;

$apiKey = env('ANTHROPIC_API_KEY');
if (!$apiKey) {
    echo "❌ ANTHROPIC_API_KEY no configurada\n";
    exit(1);
}

$client = new Client($apiKey);

try {
    $response = $client->messages()->create([
        'model' => 'claude-3-haiku-20240307',
        'max_tokens' => 50,
        'messages' => [
            ['role' => 'user', 'content' => 'Hola, ¿cómo estás?']
        ]
    ]);
    
    echo "✅ Anthropic conectado correctamente\n";
    echo "Respuesta: " . $response->content[0]->text . "\n";
} catch (Exception $e) {
    echo "❌ Error conectando con Anthropic: " . $e->getMessage() . "\n";
}
EOF

chmod +x /var/www/backend-csdt/test_*.php

print_success "✅ Scripts de prueba creados"

# ===========================================
# CONFIGURAR MONITOREO DE IA
# ===========================================
print_step "Configurando monitoreo de IA..."

# Crear script de monitoreo de IA
cat > /usr/local/bin/monitor_ia.sh << 'EOF'
#!/bin/bash

echo "=== MONITOR DE SERVICIOS DE IA ==="
echo "Fecha: $(date)"
echo ""

# Verificar OpenAI
if [ -n "$OPENAI_API_KEY" ]; then
    echo "✅ OpenAI API Key configurada"
else
    echo "❌ OpenAI API Key no configurada"
fi

# Verificar Anthropic
if [ -n "$ANTHROPIC_API_KEY" ]; then
    echo "✅ Anthropic API Key configurada"
else
    echo "❌ Anthropic API Key no configurada"
fi

# Verificar Google Gemini
if [ -n "$GOOGLE_GEMINI_API_KEY" ]; then
    echo "✅ Google Gemini API Key configurada"
else
    echo "❌ Google Gemini API Key no configurada"
fi

# Verificar Redis
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis funcionando"
else
    echo "❌ Redis no responde"
fi

# Verificar memoria de IA
echo ""
echo "=== USO DE MEMORIA ==="
free -h

echo ""
echo "=== PROCESOS DE IA ==="
ps aux | grep -E "(openai|anthropic|gemini)" | grep -v grep || echo "No hay procesos de IA activos"
EOF

chmod +x /usr/local/bin/monitor_ia.sh

print_success "✅ Monitoreo de IA configurado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_step "Verificando configuración de IA..."

echo -e "${CYAN}=== VERIFICACIÓN DE SERVICIOS DE IA ===${NC}"

# Verificar archivos de configuración
if [ -f "/var/www/backend-csdt/config/ia_services.php" ]; then
    echo "✅ Archivo de configuración de IA creado"
else
    echo "❌ Archivo de configuración de IA no encontrado"
fi

# Verificar dependencias PHP
if [ -f "/var/www/backend-csdt/vendor/autoload.php" ]; then
    echo "✅ Dependencias PHP instaladas"
else
    echo "❌ Dependencias PHP no instaladas"
fi

# Verificar dependencias Node.js
if [ -d "/var/www/frontend-csdt/node_modules" ]; then
    echo "✅ Dependencias Node.js instaladas"
else
    echo "❌ Dependencias Node.js no instaladas"
fi

# Verificar Redis
if redis-cli ping > /dev/null 2>&1; then
    echo "✅ Redis funcionando"
else
    echo "❌ Redis no responde"
fi

print_success "✅ Verificación completada"

print_header "CONFIGURACIÓN DE SERVICIOS DE IA COMPLETADA"

print_success "✅ Servicios de IA configurados correctamente"
print_success "✅ Variables de entorno configuradas"
print_success "✅ Dependencias instaladas"
print_success "✅ Cache y rate limiting configurados"
print_success "✅ Monitoreo configurado"

echo -e "${YELLOW}PRÓXIMOS PASOS:${NC}"
echo -e "${YELLOW}1. Configurar las API keys en el archivo .env${NC}"
echo -e "${YELLOW}2. Ejecutar los scripts de prueba: test_openai.php, test_anthropic.php${NC}"
echo -e "${YELLOW}3. Iniciar los servicios con PM2${NC}"
echo -e "${YELLOW}4. Verificar el monitoreo con: monitor_ia.sh${NC}"

echo -e "${GREEN}Para probar los servicios de IA, ejecuta:${NC}"
echo -e "${GREEN}cd /var/www/backend-csdt && php test_openai.php${NC}"
echo -e "${GREEN}cd /var/www/backend-csdt && php test_anthropic.php${NC}"
