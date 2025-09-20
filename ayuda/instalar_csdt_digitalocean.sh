#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN AUTOMATIZADA CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
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

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"
REPO_FRONTEND="https://github.com/veeduriacsdc-ui/frontend-csdt"
REPO_BACKEND="https://github.com/veeduriacsdc-ui/backend-csdt.git"

print_header "INICIANDO INSTALACIÓN CSDT EN DIGITALOCEAN"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_csdt_digitalocean.sh)"
    exit 1
fi

# ===========================================
# PASO 1: ACTUALIZAR SISTEMA
# ===========================================
print_header "PASO 1: ACTUALIZANDO SISTEMA"

print_message "Actualizando paquetes del sistema..."
apt update && apt upgrade -y

print_message "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget unzip git

# ===========================================
# PASO 2: INSTALAR PHP 8.2
# ===========================================
print_header "PASO 2: INSTALANDO PHP 8.2"

print_message "Agregando repositorio de PHP..."
add-apt-repository ppa:ondrej/php -y
apt update

print_message "Instalando PHP 8.2 y extensiones..."
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3

# ===========================================
# PASO 3: INSTALAR COMPOSER
# ===========================================
print_header "PASO 3: INSTALANDO COMPOSER"

print_message "Descargando e instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# ===========================================
# PASO 4: INSTALAR NODE.JS 18.x
# ===========================================
print_header "PASO 4: INSTALANDO NODE.JS 18.x"

print_message "Agregando repositorio de Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -

print_message "Instalando Node.js y npm..."
apt-get install -y nodejs

# ===========================================
# PASO 5: INSTALAR NGINX Y PM2
# ===========================================
print_header "PASO 5: INSTALANDO NGINX Y PM2"

print_message "Instalando Nginx..."
apt install -y nginx

print_message "Instalando PM2 globalmente..."
npm install -g pm2

# ===========================================
# PASO 6: INSTALAR MYSQL 8.0
# ===========================================
print_header "PASO 6: INSTALANDO MYSQL 8.0"

print_message "Instalando MySQL 8.0..."
apt install -y mysql-server-8.0

print_message "Configurando MySQL..."
mysql_secure_installation <<EOF

y
y
y
y
y
EOF

# ===========================================
# PASO 7: CONFIGURAR MYSQL
# ===========================================
print_header "PASO 7: CONFIGURANDO MYSQL"

print_message "Creando base de datos y usuario..."
mysql -u root -e "
CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY 'csdt_password_2024';
GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
FLUSH PRIVILEGES;
SHOW DATABASES;
"

# ===========================================
# PASO 8: CONFIGURAR BACKEND
# ===========================================
print_header "PASO 8: CONFIGURANDO BACKEND"

print_message "Creando directorio del backend..."
mkdir -p /var/www/backend-csdt
cd /var/www/backend-csdt

print_message "Clonando repositorio del backend..."
git clone $REPO_BACKEND .

print_message "Instalando dependencias PHP..."
composer install --optimize-autoloader --no-dev

print_message "Instalando dependencias Node.js..."
npm install

# ===========================================
# PASO 9: CONFIGURAR ARCHIVO .ENV DEL BACKEND
# ===========================================
print_header "PASO 9: CONFIGURANDO ARCHIVO .ENV DEL BACKEND"

print_message "Creando archivo .env para el backend..."
cat > .env << 'EOF'
# ===========================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ===========================================
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=production
APP_KEY=
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
DB_PASSWORD=csdt_password_2024
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
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=
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

# ===========================================
# PASO 10: CONFIGURAR LARAVEL
# ===========================================
print_header "PASO 10: CONFIGURANDO LARAVEL"

print_message "Generando clave de aplicación..."
php artisan key:generate

print_message "Ejecutando migraciones..."
php artisan migrate --force

print_message "Ejecutando seeders..."
php artisan db:seed --force

print_message "Limpiando y optimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_message "Configurando permisos..."
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

# ===========================================
# PASO 11: CREAR ECOSYSTEM.CONFIG.JS PARA BACKEND
# ===========================================
print_header "PASO 11: CREANDO ECOSYSTEM.CONFIG.JS PARA BACKEND"

print_message "Creando archivo ecosystem.config.js..."
cat > ecosystem.config.js << 'EOF'
module.exports = {
    apps: [{
        name: 'backend-csdt',
        script: 'artisan',
        args: 'serve --host=0.0.0.0 --port=8000',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'development',
            APP_ENV: 'local'
        },
        env_production: {
            NODE_ENV: 'production',
            APP_ENV: 'production'
        },
        log_file: '/var/log/backend-csdt/combined.log',
        out_file: '/var/log/backend-csdt/out.log',
        error_file: '/var/log/backend-csdt/error.log',
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        merge_logs: true,
        max_memory_restart: '512M',
        watch: false,
        ignore_watch: ['node_modules', 'storage/logs', 'vendor'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
EOF

# ===========================================
# PASO 12: CONFIGURAR FRONTEND
# ===========================================
print_header "PASO 12: CONFIGURANDO FRONTEND"

print_message "Creando directorio del frontend..."
mkdir -p /var/www/frontend-csdt
cd /var/www/frontend-csdt

print_message "Clonando repositorio del frontend..."
git clone $REPO_FRONTEND .

print_message "Instalando dependencias del frontend..."
npm install

# ===========================================
# PASO 13: CONFIGURAR ARCHIVO .ENV DEL FRONTEND
# ===========================================
print_header "PASO 13: CONFIGURANDO ARCHIVO .ENV DEL FRONTEND"

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

# ===========================================
# PASO 14: COMPILAR FRONTEND
# ===========================================
print_header "PASO 14: COMPILANDO FRONTEND"

print_message "Compilando frontend para producción..."
npm run build

print_message "Configurando permisos del frontend..."
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

# ===========================================
# PASO 15: CREAR ECOSYSTEM-FRONTEND.CONFIG.JS
# ===========================================
print_header "PASO 15: CREANDO ECOSYSTEM-FRONTEND.CONFIG.JS"

print_message "Creando archivo ecosystem-frontend.config.js..."
cat > ecosystem-frontend.config.js << 'EOF'
module.exports = {
    apps: [{
        name: 'frontend-csdt',
        script: 'npm',
        args: 'run preview -- --host 0.0.0.0 --port 3000',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'production'
        },
        log_file: '/var/log/frontend-csdt/combined.log',
        out_file: '/var/log/frontend-csdt/out.log',
        error_file: '/var/log/frontend-csdt/error.log',
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        merge_logs: true,
        max_memory_restart: '512M',
        watch: false,
        ignore_watch: ['node_modules', 'dist'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
EOF

# ===========================================
# PASO 16: CONFIGURAR FIREWALL
# ===========================================
print_header "PASO 16: CONFIGURANDO FIREWALL"

print_message "Configurando UFW..."
ufw allow OpenSSH
ufw allow 3000
ufw allow 8000
ufw allow 'Nginx Full'
ufw --force enable

# ===========================================
# PASO 17: INICIAR SERVICIOS CON PM2
# ===========================================
print_header "PASO 17: INICIANDO SERVICIOS CON PM2"

print_message "Creando directorios de logs..."
mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
chown -R www-data:www-data /var/log/backend-csdt
chown -R www-data:www-data /var/log/frontend-csdt

print_message "Iniciando backend con PM2..."
cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production

print_message "Iniciando frontend con PM2..."
cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js

print_message "Configurando PM2 para inicio automático..."
pm2 startup
pm2 save

# ===========================================
# PASO 18: VERIFICACIÓN FINAL
# ===========================================
print_header "PASO 18: VERIFICACIÓN FINAL"

print_message "Verificando estado de PM2..."
pm2 status

print_message "Verificando que la API responda..."
curl -I http://localhost:8000 || print_warning "API no responde aún"

print_message "Verificando que el frontend responda..."
curl -I http://localhost:3000 || print_warning "Frontend no responde aún"

print_message "Verificando accesibilidad externa..."
curl -I http://$IP_PUBLICA:3000 || print_warning "Frontend no accesible externamente aún"
curl -I http://$IP_PUBLICA:8000 || print_warning "API no accesible externamente aún"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "INSTALACIÓN COMPLETADA"

print_message "✅ Backend Laravel instalado y configurado"
print_message "✅ Frontend React/Vite instalado y compilado"
print_message "✅ Servicios de IA configurados"
print_message "✅ Base de datos MySQL configurada"
print_message "✅ PM2 configurado para gestión de procesos"
print_message "✅ Firewall configurado"

print_message "URLs de acceso:"
print_message "Frontend: http://$IP_PUBLICA:3000"
print_message "API Backend: http://$IP_PUBLICA:8000"

print_warning "IMPORTANTE:"
print_warning "1. Configura las credenciales de email en el archivo .env del backend"
print_warning "2. Configura las API keys de IA en el archivo .env del frontend"
print_warning "3. Verifica que los servicios estén corriendo con: pm2 status"
print_warning "4. Revisa los logs con: pm2 logs"

print_message "¡Sistema CSDT instalado exitosamente!"
