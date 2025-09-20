#!/bin/bash

# 🚀 SCRIPT DE INSTALACIÓN AUTOMÁTICA PARA DIGITALOCEAN
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# Versión: 1.0.0

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes con colores
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
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Verificar que se ejecute como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root: sudo bash instalar_digitalocean.sh"
    exit 1
fi

print_header "🚀 INSTALACIÓN AUTOMÁTICA CSDT EN DIGITALOCEAN"

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"
BACKEND_REPO="https://github.com/veeduriacsdc-ui/backend-csdt.git"
FRONTEND_REPO="https://github.com/veeduriacsdc-ui/frontend-csdt.git"

print_message "Iniciando instalación en servidor $IP_PUBLICA"

# PASO 1: Actualizar sistema
print_header "PASO 1: ACTUALIZANDO SISTEMA"
print_message "Actualizando paquetes del sistema..."
apt update && apt upgrade -y

# PASO 2: Instalar dependencias básicas
print_header "PASO 2: INSTALANDO DEPENDENCIAS BÁSICAS"
print_message "Instalando PHP 8.2 y extensiones..."

# Instalar PHP 8.2
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3 -y

# Instalar Composer
print_message "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Instalar Node.js 18.x
print_message "Instalando Node.js 18.x..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Instalar Git
print_message "Instalando Git..."
apt install git -y

# Instalar PM2
print_message "Instalando PM2..."
npm install -g pm2

# Instalar MySQL 8.0
print_message "Instalando MySQL 8.0..."
apt install mysql-server-8.0 -y

# Instalar herramientas adicionales
print_message "Instalando herramientas adicionales..."
apt install unzip wget curl -y

# Verificar instalaciones
print_message "Verificando instalaciones..."
php --version
composer --version
node --version
npm --version
git --version
pm2 --version

# PASO 3: Configurar MySQL
print_header "PASO 3: CONFIGURANDO MYSQL"
print_message "Configurando MySQL..."

# Crear base de datos y usuario
mysql -u root -e "
CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY 'rawkmxypkwrwoexu';
GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
FLUSH PRIVILEGES;
"

print_message "Base de datos MySQL configurada correctamente"

# PASO 4: Configurar Backend
print_header "PASO 4: CONFIGURANDO BACKEND (LARAVEL)"

# Crear directorio del backend
print_message "Creando directorio del backend..."
mkdir -p /var/www/backend-csdt
cd /var/www/backend-csdt

# Clonar repositorio
print_message "Clonando repositorio del backend..."
git clone $BACKEND_REPO .

# Instalar dependencias PHP
print_message "Instalando dependencias PHP..."
composer install --optimize-autoloader --no-dev

# Instalar dependencias Node.js
print_message "Instalando dependencias Node.js..."
npm install

# Crear archivo .env
print_message "Creando archivo .env para el backend..."
cat > .env << 'EOF'
# ===========================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ===========================================
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=production
APP_KEY=base64:jagtyEX3Li6k9kxbgV3WXsWA+6lDpFkkAze0n4wKIbU=
APP_DEBUG=false
APP_URL=http://64.225.113.49:8000
APP_TIMEZONE=America/Bogota
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

# ===========================================
# CONFIGURACIÓN DE BASE DE DATOS (MYSQL)
# ===========================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_final
DB_USERNAME=csdt
DB_PASSWORD=rawkmxypkwrwoexu
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

# ===========================================
# CONFIGURACIÓN DE CACHE Y SESIONES
# ===========================================
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

# ===========================================
# CONFIGURACIÓN DE SESIONES MEJORADA
# ===========================================
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_COOKIE_NAME=csdt_session
SESSION_COOKIE_PATH=/
SESSION_COOKIE_DOMAIN=64.225.113.49

# ===========================================
# CONFIGURACIÓN DE MAIL
# ===========================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=csdtjusticia@gmail.com
MAIL_PASSWORD="rawkmxypkwrwoexu"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=csdtjusticia@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

# ===========================================
# CONFIGURACIÓN DE SANCTUM
# ===========================================
SANCTUM_STATEFUL_DOMAINS=64.225.113.49:3000,64.225.113.49:8000
SESSION_DOMAIN=64.225.113.49
SANCTUM_GUARD=web
SANCTUM_MIDDLEWARE=throttle:api

# ===========================================
# CONFIGURACIÓN DE CORS
# ===========================================
CORS_ALLOWED_ORIGINS=http://64.225.113.49:3000,http://localhost:3000
CORS_ALLOWED_HEADERS=Accept,Authorization,Content-Type,X-Requested-With,X-CSRF-TOKEN,X-XSRF-TOKEN
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS,PATCH
CORS_SUPPORTS_CREDENTIALS=true
CORS_MAX_AGE=86400
CORS_EXPOSED_HEADERS=Authorization

# ===========================================
# CONFIGURACIÓN DE LOGS
# ===========================================
LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DEPRECATIONS_CHANNEL=null

# ===========================================
# CONFIGURACIÓN DE ARCHIVOS
# ===========================================
UPLOAD_PATH=/var/www/backend-csdt/storage/uploads
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx

# ===========================================
# CONFIGURACIÓN DE REDIS (OPCIONAL)
# ===========================================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ===========================================
# CONFIGURACIÓN DE IA (OPCIONAL)
# ===========================================
OPENAI_API_KEY=
ANTHROPIC_API_KEY=

# ===========================================
# CONFIGURACIÓN DE SENTRY (OPCIONAL)
# ===========================================
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

# ===========================================
# CONFIGURACIÓN DE SEGURIDAD
# ===========================================
BCRYPT_ROUNDS=12

# ===========================================
# CONFIGURACIÓN DE DESARROLLO
# ===========================================
VITE_APP_NAME="${APP_NAME}"
VITE_APP_ENV=production
EOF

# Configurar permisos
print_message "Configurando permisos del backend..."
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

# Ejecutar migraciones
print_message "Ejecutando migraciones de la base de datos..."
php artisan migrate --force

# Ejecutar seeders
print_message "Ejecutando seeders..."
php artisan db:seed --force

# Limpiar y optimizar cache
print_message "Limpiando y optimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar archivo de configuración PM2
print_message "Verificando archivo de configuración PM2 del backend..."
if [ -f "ecosystem.config.js" ]; then
    print_message "✅ Archivo ecosystem.config.js encontrado"
else
    print_error "❌ Archivo ecosystem.config.js no encontrado"
    exit 1
fi

# Crear directorio de logs y iniciar PM2
print_message "Configurando PM2 para el backend..."
mkdir -p /var/log/backend-csdt
chown -R www-data:www-data /var/log/backend-csdt
pm2 start ecosystem.config.js --env production
pm2 startup
pm2 save

# PASO 5: Configurar Frontend
print_header "PASO 5: CONFIGURANDO FRONTEND (REACT + VITE)"

# Crear directorio del frontend
print_message "Creando directorio del frontend..."
mkdir -p /var/www/frontend-csdt
cd /var/www/frontend-csdt

# Clonar repositorio
print_message "Clonando repositorio del frontend..."
git clone $FRONTEND_REPO .

# Instalar dependencias
print_message "Instalando dependencias del frontend..."
npm install

# Crear archivo .env
print_message "Creando archivo .env para el frontend..."
cat > .env << 'EOF'
# ===========================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ===========================================
VITE_APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
VITE_APP_VERSION="1.0.0"
VITE_APP_ENV=production

# ===========================================
# CONFIGURACIÓN DE API
# ===========================================
VITE_API_URL=http://64.225.113.49:8000
VITE_API_TIMEOUT=30000

# ===========================================
# CONFIGURACIÓN DE SERVICIOS DE IA
# ===========================================
VITE_IA_ENABLED=true
VITE_IA_SERVICES_ENABLED=true
VITE_IA_MEJORADA_ENABLED=true
VITE_IA_PROFESIONALES_ENABLED=true
VITE_IA_SISTEMA_PROFESIONAL_ENABLED=true
VITE_IA_CHAT_MEJORADO_ENABLED=true
VITE_IA_TECNICAS_ENABLED=true
VITE_IA_CONSEJO_ENABLED=true

# ===========================================
# CONFIGURACIÓN DE MAPAS
# ===========================================
VITE_LEAFLET_ENABLED=true
VITE_MAPBOX_TOKEN=tu_mapbox_token_aqui
VITE_GEODJANGO_ENABLED=true

# ===========================================
# CONFIGURACIÓN DE NOTIFICACIONES
# ===========================================
VITE_NOTIFICATIONS_ENABLED=true
VITE_PUSH_NOTIFICATIONS=false

# ===========================================
# CONFIGURACIÓN DE DESARROLLO
# ===========================================
VITE_DEBUG=false
VITE_VERBOSE_LOGGING=false

# ===========================================
# CONFIGURACIÓN DE SERVICIOS DE IA
# ===========================================
VITE_OPENAI_API_KEY=tu_api_key_de_openai_aqui
VITE_IA_TIMEOUT=30000
VITE_IA_MAX_TOKENS=2000
VITE_IA_TEMPERATURE=0.7
EOF

# Compilar frontend
print_message "Compilando frontend para producción..."
npm run build

# Configurar permisos
print_message "Configurando permisos del frontend..."
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

# Verificar archivo de configuración PM2 para frontend
print_message "Verificando archivo de configuración PM2 del frontend..."
if [ -f "ecosystem-frontend.config.js" ]; then
    print_message "✅ Archivo ecosystem-frontend.config.js encontrado"
else
    print_error "❌ Archivo ecosystem-frontend.config.js no encontrado"
    exit 1
fi

# Crear directorio de logs y iniciar PM2 para frontend
print_message "Configurando PM2 para el frontend..."
mkdir -p /var/log/frontend-csdt
chown -R www-data:www-data /var/log/frontend-csdt
pm2 start ecosystem-frontend.config.js
pm2 save

# PASO 6: Configurar Firewall
print_header "PASO 6: CONFIGURANDO FIREWALL"
print_message "Configurando firewall UFW..."

# Configurar UFW
ufw --force enable
ufw allow OpenSSH
ufw allow 3000
ufw allow 8000

print_message "Firewall configurado correctamente"

# PASO 7: Verificación final
print_header "PASO 7: VERIFICACIÓN FINAL"

# Verificar estado de PM2
print_message "Verificando estado de PM2..."
pm2 status

# Verificar que los servicios respondan
print_message "Verificando que los servicios respondan..."
sleep 5

# Verificar backend
if curl -s http://localhost:8000 > /dev/null; then
    print_message "✅ Backend funcionando correctamente en puerto 8000"
else
    print_warning "⚠️ Backend no responde en puerto 8000"
fi

# Verificar frontend
if curl -s http://localhost:3000 > /dev/null; then
    print_message "✅ Frontend funcionando correctamente en puerto 3000"
else
    print_warning "⚠️ Frontend no responde en puerto 3000"
fi

# Mostrar URLs de acceso
print_header "🎉 INSTALACIÓN COMPLETADA"
print_message "URLs de acceso:"
echo -e "${GREEN}Frontend:${NC} http://$IP_PUBLICA:3000"
echo -e "${GREEN}Backend API:${NC} http://$IP_PUBLICA:8000"
echo ""
print_message "Comandos útiles:"
echo -e "${BLUE}Ver estado de servicios:${NC} pm2 status"
echo -e "${BLUE}Ver logs del backend:${NC} pm2 logs backend-csdt"
echo -e "${BLUE}Ver logs del frontend:${NC} pm2 logs frontend-csdt"
echo -e "${BLUE}Reiniciar servicios:${NC} pm2 restart all"
echo ""
print_message "¡La instalación se completó exitosamente!"
