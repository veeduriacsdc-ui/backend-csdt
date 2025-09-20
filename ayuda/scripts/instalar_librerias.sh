#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE LIBRERÍAS COMPLETO
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# UBUNTU/DIGITALOCEAN - VERSIÓN MEJORADA
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
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_header "INSTALACIÓN COMPLETA DE LIBRERÍAS CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_librerias.sh)"
    exit 1
fi

# ===========================================
# ACTUALIZAR SISTEMA
# ===========================================
print_step "Actualizando sistema Ubuntu..."

apt update && apt upgrade -y
apt install -y software-properties-common apt-transport-https ca-certificates gnupg lsb-release

print_success "✅ Sistema actualizado"

# ===========================================
# INSTALAR LIBRERÍAS DEL SISTEMA
# ===========================================
print_step "Instalando librerías del sistema..."

# Librerías básicas esenciales
apt install -y curl wget git unzip zip tar gzip bzip2 xz-utils

# Librerías de desarrollo completas
apt install -y build-essential libssl-dev libffi-dev python3-dev python3-pip python3-venv
apt install -y pkg-config autoconf automake libtool make cmake

# Librerías de base de datos completas
apt install -y libmysqlclient-dev libpq-dev libsqlite3-dev
apt install -y mysql-client postgresql-client sqlite3

# Librerías de imagen y multimedia
apt install -y libjpeg-dev libpng-dev libwebp-dev libtiff-dev libfreetype6-dev
apt install -y libavcodec-dev libavformat-dev libavutil-dev libswscale-dev

# Librerías de compresión y archivos
apt install -y zlib1g-dev libbz2-dev liblzma-dev libzip-dev

# Librerías de red y seguridad
apt install -y libcurl4-openssl-dev libxml2-dev libxslt1-dev
apt install -y libssl-dev libcrypto++-dev libsodium-dev

# Librerías de sistema
apt install -y libc6-dev libncurses5-dev libreadline-dev
apt install -y libgdbm-dev libnss3-dev libtinfo-dev

print_success "✅ Librerías del sistema instaladas"

# ===========================================
# INSTALAR PHP 8.2 COMPLETO
# ===========================================
print_step "Instalando PHP 8.2 y extensiones..."

# Agregar repositorio de PHP
add-apt-repository ppa:ondrej/php -y
apt update

# Instalar PHP 8.2 y extensiones esenciales
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-common php8.2-dev

# Extensiones de base de datos
apt install -y php8.2-mysql php8.2-pgsql php8.2-sqlite3 php8.2-pdo

# Extensiones de red y HTTP
apt install -y php8.2-curl php8.2-http php8.2-json php8.2-mbstring php8.2-xml php8.2-xmlrpc php8.2-soap

# Extensiones de imagen y multimedia
apt install -y php8.2-gd php8.2-imagick php8.2-exif php8.2-fileinfo

# Extensiones de compresión y archivos
apt install -y php8.2-zip php8.2-bz2 php8.2-phar

# Extensiones de matemáticas y criptografía
apt install -y php8.2-bcmath php8.2-gmp php8.2-mcrypt php8.2-openssl

# Extensiones de internacionalización
apt install -y php8.2-intl php8.2-gettext

# Extensiones de cache y performance
apt install -y php8.2-redis php8.2-memcached php8.2-opcache php8.2-apcu

# Extensiones adicionales para IA y procesamiento
apt install -y php8.2-tidy php8.2-xsl php8.2-readline php8.2-pspell

print_success "✅ PHP 8.2 y extensiones instaladas"

# ===========================================
# INSTALAR NODE.JS 18 LTS
# ===========================================
print_step "Instalando Node.js 18 LTS..."

# Instalar Node.js 18 LTS
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Verificar instalación
node --version
npm --version

# Instalar librerías globales de Node.js
npm install -g pm2
npm install -g nodemon
npm install -g typescript
npm install -g @vitejs/plugin-react
npm install -g concurrently
npm install -g cross-env

print_success "✅ Node.js 18 LTS y librerías instaladas"

# ===========================================
# INSTALAR COMPOSER
# ===========================================
print_step "Instalando Composer..."

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Configurar Composer
composer config -g repo.packagist composer https://packagist.org
composer global require hirak/prestissimo

print_success "✅ Composer instalado"

# ===========================================
# INSTALAR LIBRERÍAS DEL BACKEND
# ===========================================
print_step "Instalando librerías del backend..."

cd /var/www/backend-csdt

# Instalar dependencias PHP con optimizaciones
composer install --optimize-autoloader --no-dev --prefer-dist

# Instalar dependencias Node.js del backend
npm install --production

print_success "✅ Librerías del backend instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DEL FRONTEND
# ===========================================
print_step "Instalando librerías del frontend..."

cd /var/www/frontend-csdt

# Instalar dependencias del frontend
npm install --production

# Instalar librerías adicionales para IA y funcionalidades
npm install axios leaflet react-leaflet chart.js react-chartjs-2
npm install jspdf jspdf-autotable html2canvas
npm install react-speech-recognition styled-components

print_success "✅ Librerías del frontend instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE PYTHON PARA IA
# ===========================================
print_step "Instalando librerías de Python para IA..."

# Instalar librerías de Python para IA
pip3 install --upgrade pip
pip3 install openai anthropic requests
pip3 install numpy pandas scikit-learn
pip3 install transformers torch torchvision
pip3 install opencv-python pillow
pip3 install speechrecognition pyaudio
pip3 install elevenlabs google-cloud-speech

print_success "✅ Librerías de Python para IA instaladas"

# ===========================================
# INSTALAR REDIS Y MYSQL
# ===========================================
print_step "Instalando Redis y MySQL..."

# Instalar Redis
apt install -y redis-server

# Instalar MySQL
apt install -y mysql-server mysql-client

# Configurar servicios
systemctl enable redis-server
systemctl start redis-server
systemctl enable mysql
systemctl start mysql

print_success "✅ Redis y MySQL instalados"

# ===========================================
# INSTALAR LIBRERÍAS DE MONITOREO Y HERRAMIENTAS
# ===========================================
print_step "Instalando librerías de monitoreo..."

# Herramientas de monitoreo del sistema
apt install -y htop iotop nethogs nload
apt install -y tree ncdu duf

# Herramientas de red
apt install -y netstat-nat tcpdump wireshark-common
apt install -y nmap netcat-openbsd

# Herramientas de desarrollo
apt install -y vim nano emacs
apt install -y git git-lfs

print_success "✅ Librerías de monitoreo instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE SEGURIDAD
# ===========================================
print_step "Instalando librerías de seguridad..."

# Instalar fail2ban
apt install -y fail2ban ufw

# Configurar fail2ban
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3

[nginx-http-auth]
enabled = true
port = http,https
logpath = /var/log/nginx/error.log
maxretry = 3
EOF

# Configurar UFW
ufw --force enable
ufw allow ssh
ufw allow 80
ufw allow 443
ufw allow 8000
ufw allow 3000

systemctl enable fail2ban
systemctl start fail2ban

print_success "✅ Librerías de seguridad instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE BACKUP
# ===========================================
print_step "Instalando librerías de backup..."

# Instalar rsync y herramientas de backup
apt install -y rsync rclone

# Instalar librerías de compresión
apt install -y zip unzip p7zip-full tar gzip bzip2

# Instalar herramientas de sincronización
apt install -y unison

print_success "✅ Librerías de backup instaladas"

# ===========================================
# CONFIGURAR PERMISOS Y ESTRUCTURA
# ===========================================
print_step "Configurando permisos y estructura..."

# Crear directorios necesarios
mkdir -p /var/www/backend-csdt
mkdir -p /var/www/frontend-csdt
mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
mkdir -p /var/backups/database

# Configurar permisos
chown -R www-data:www-data /var/www/
chmod -R 755 /var/www/

print_success "✅ Permisos y estructura configurados"

# ===========================================
# VERIFICAR INSTALACIÓN COMPLETA
# ===========================================
print_step "Verificando instalación completa..."

echo -e "${CYAN}=== VERIFICACIÓN DE COMPONENTES ===${NC}"

# Verificar PHP
echo -e "${GREEN}PHP:${NC}"
php --version
php -m | grep -E "(mysql|pgsql|sqlite|curl|gd|mbstring|xml|zip|bcmath|intl|redis)"

# Verificar Composer
echo -e "${GREEN}Composer:${NC}"
composer --version

# Verificar Node.js
echo -e "${GREEN}Node.js:${NC}"
node --version
npm --version

# Verificar PM2
echo -e "${GREEN}PM2:${NC}"
pm2 --version

# Verificar servicios
echo -e "${GREEN}Servicios:${NC}"
systemctl is-active redis-server && echo "✅ Redis activo" || echo "❌ Redis inactivo"
systemctl is-active mysql && echo "✅ MySQL activo" || echo "❌ MySQL inactivo"
systemctl is-active fail2ban && echo "✅ Fail2ban activo" || echo "❌ Fail2ban inactivo"

# Verificar extensiones PHP críticas
echo -e "${GREEN}Extensiones PHP críticas:${NC}"
php -m | grep -E "(OpenAI|Anthropic|Google|LexisNexis)" || echo "⚠️ Extensiones de IA no detectadas"

print_success "✅ Verificación completada"

# ===========================================
# OPTIMIZAR CONFIGURACIÓN
# ===========================================
print_step "Optimizando configuración..."

# Optimizar PHP para CSDT
cat >> /etc/php/8.2/fpm/php.ini << 'EOF'
; Configuración optimizada para CSDT
memory_limit = 1024M
max_execution_time = 300
upload_max_filesize = 100M
post_max_size = 100M
max_input_vars = 5000
max_file_uploads = 20
date.timezone = America/Bogota
opcache.enable = 1
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0
EOF

# Optimizar PHP CLI
cat >> /etc/php/8.2/cli/php.ini << 'EOF'
; Configuración CLI optimizada para CSDT
memory_limit = 2048M
max_execution_time = 0
date.timezone = America/Bogota
EOF

# Reiniciar servicios
systemctl restart php8.2-fpm
systemctl restart nginx 2>/dev/null || true

print_success "✅ Configuración optimizada"

# ===========================================
# CREAR ARCHIVO DE CONFIGURACIÓN DE IA
# ===========================================
print_step "Creando configuración de servicios de IA..."

cat > /var/www/backend-csdt/config/ia_services.php << 'EOF'
<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'timeout' => 30,
    ],
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        'timeout' => 30,
    ],
    'google_gemini' => [
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'base_url' => env('GOOGLE_GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-pro'),
        'timeout' => 30,
    ],
    'lexisnexis' => [
        'api_key' => env('LEXISNEXIS_API_KEY'),
        'base_url' => env('LEXISNEXIS_BASE_URL'),
        'timeout' => 30,
    ],
    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
        'timeout' => 30,
    ],
    'google_speech' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'credentials_path' => env('GOOGLE_CLOUD_CREDENTIALS_PATH'),
        'timeout' => 30,
    ],
];
EOF

print_success "✅ Configuración de IA creada"

print_header "INSTALACIÓN COMPLETA DE LIBRERÍAS FINALIZADA"

print_success "✅ Todas las librerías instaladas correctamente"
print_success "✅ Sistema optimizado para CSDT"
print_success "✅ Servicios de IA configurados"
print_success "✅ Seguridad configurada"
print_success "✅ Monitoreo configurado"

echo -e "${YELLOW}PRÓXIMOS PASOS:${NC}"
echo -e "${YELLOW}1. Configurar variables de entorno (.env)${NC}"
echo -e "${YELLOW}2. Ejecutar migraciones de base de datos${NC}"
echo -e "${YELLOW}3. Configurar servicios de IA${NC}"
echo -e "${YELLOW}4. Iniciar servicios con PM2${NC}"

echo -e "${GREEN}Para verificar el estado, ejecuta: ./verificar_csdt.sh${NC}"
