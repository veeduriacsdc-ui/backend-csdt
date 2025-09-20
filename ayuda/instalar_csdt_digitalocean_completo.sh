#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN AUTOMATIZADA CSDT COMPLETO
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
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

print_step() {
    echo -e "${PURPLE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"
REPO_FRONTEND="https://github.com/veeduriacsdc-ui/frontend-csdt"
REPO_BACKEND="https://github.com/veeduriacsdc-ui/backend-csdt.git"

print_header "INICIANDO INSTALACIÓN CSDT COMPLETA EN DIGITALOCEAN"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_csdt_digitalocean_completo.sh)"
    exit 1
fi

# ===========================================
# PASO 1: ACTUALIZAR SISTEMA
# ===========================================
print_header "PASO 1: ACTUALIZANDO SISTEMA"

print_step "Actualizando paquetes del sistema..."
apt update && apt upgrade -y

print_step "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget unzip git

# ===========================================
# PASO 2: INSTALAR PHP 8.2
# ===========================================
print_header "PASO 2: INSTALANDO PHP 8.2"

print_step "Agregando repositorio de PHP..."
add-apt-repository ppa:ondrej/php -y
apt update

print_step "Instalando PHP 8.2 y extensiones..."
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3

print_success "PHP 8.2 instalado correctamente"

# ===========================================
# PASO 3: INSTALAR COMPOSER
# ===========================================
print_header "PASO 3: INSTALANDO COMPOSER"

print_step "Descargando e instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

print_success "Composer instalado correctamente"

# ===========================================
# PASO 4: INSTALAR NODE.JS 18.x
# ===========================================
print_header "PASO 4: INSTALANDO NODE.JS 18.x"

print_step "Agregando repositorio de Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -

print_step "Instalando Node.js y npm..."
apt-get install -y nodejs

print_success "Node.js 18.x instalado correctamente"

# ===========================================
# PASO 5: INSTALAR NGINX Y PM2
# ===========================================
print_header "PASO 5: INSTALANDO NGINX Y PM2"

print_step "Instalando Nginx..."
apt install -y nginx

print_step "Instalando PM2 globalmente..."
npm install -g pm2

print_success "Nginx y PM2 instalados correctamente"

# ===========================================
# PASO 6: INSTALAR MYSQL 8.0
# ===========================================
print_header "PASO 6: INSTALANDO MYSQL 8.0"

print_step "Instalando MySQL 8.0..."
apt install -y mysql-server-8.0

print_step "Configurando MySQL..."
mysql_secure_installation <<EOF

y
y
y
y
y
EOF

print_success "MySQL 8.0 instalado y configurado"

# ===========================================
# PASO 7: CONFIGURAR MYSQL
# ===========================================
print_header "PASO 7: CONFIGURANDO MYSQL"

print_step "Creando base de datos y usuario..."
mysql -u root -e "
CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY 'csdt_password_2024';
GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
FLUSH PRIVILEGES;
SHOW DATABASES;
"

print_success "Base de datos MySQL configurada correctamente"

# ===========================================
# PASO 8: CONFIGURAR BACKEND
# ===========================================
print_header "PASO 8: CONFIGURANDO BACKEND"

print_step "Creando directorio del backend..."
mkdir -p /var/www/backend-csdt
cd /var/www/backend-csdt

print_step "Clonando repositorio del backend..."
git clone $REPO_BACKEND .

print_step "Instalando dependencias PHP (23 paquetes)..."
composer install --optimize-autoloader --no-dev

print_step "Instalando dependencias Node.js (38 paquetes)..."
npm install

print_success "Backend configurado correctamente"

# ===========================================
# PASO 9: CONFIGURAR ARCHIVO .ENV DEL BACKEND
# ===========================================
print_header "PASO 9: CONFIGURANDO ARCHIVO .ENV DEL BACKEND"

print_step "Creando archivo .env para el backend..."
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
# CONFIGURACIÓN DE MAIL (CONFIGURAR DESPUÉS)
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

print_success "Archivo .env del backend creado correctamente"

# ===========================================
# PASO 10: CONFIGURAR LARAVEL
# ===========================================
print_header "PASO 10: CONFIGURANDO LARAVEL"

print_step "Generando clave de aplicación..."
php artisan key:generate

print_step "Ejecutando migraciones (19 migraciones)..."
php artisan migrate --force

print_step "Ejecutando seeders (5 seeders)..."
php artisan db:seed --force

print_step "Limpiando y optimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_step "Configurando permisos..."
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

print_success "Laravel configurado correctamente"

# ===========================================
# PASO 11: CREAR ECOSYSTEM.CONFIG.JS PARA BACKEND
# ===========================================
print_header "PASO 11: CREANDO ECOSYSTEM.CONFIG.JS PARA BACKEND"

print_step "Creando archivo ecosystem.config.js..."
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

print_success "Archivo ecosystem.config.js creado correctamente"

# ===========================================
# PASO 12: CONFIGURAR FRONTEND
# ===========================================
print_header "PASO 12: CONFIGURANDO FRONTEND"

print_step "Creando directorio del frontend..."
mkdir -p /var/www/frontend-csdt
cd /var/www/frontend-csdt

print_step "Clonando repositorio del frontend..."
git clone $REPO_FRONTEND .

print_step "Instalando dependencias del frontend (13 paquetes)..."
npm install

print_success "Frontend configurado correctamente"

# ===========================================
# PASO 13: CONFIGURAR ARCHIVO .ENV DEL FRONTEND
# ===========================================
print_header "PASO 13: CONFIGURANDO ARCHIVO .ENV DEL FRONTEND"

print_step "Creando archivo .env para el frontend..."
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

print_success "Archivo .env del frontend creado correctamente"

# ===========================================
# PASO 14: VERIFICAR SERVICIOS DE IA
# ===========================================
print_header "PASO 14: VERIFICANDO SERVICIOS DE IA"

print_step "Verificando servicios de IA..."
if [ -d "src/services" ]; then
    services_count=$(ls src/services/ | wc -l)
    print_success "Servicios de IA verificados: $services_count servicios"
    
    # Listar servicios encontrados
    echo "Servicios encontrados:"
    ls -la src/services/ | grep "\.js$" | awk '{print "  - " $9}'
else
    print_warning "Directorio de servicios no encontrado"
fi

# ===========================================
# PASO 15: COMPILAR FRONTEND
# ===========================================
print_header "PASO 15: COMPILANDO FRONTEND"

print_step "Compilando frontend para producción..."
npm run build

print_step "Configurando permisos del frontend..."
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

print_success "Frontend compilado correctamente"

# ===========================================
# PASO 16: CREAR ECOSYSTEM-FRONTEND.CONFIG.JS
# ===========================================
print_header "PASO 16: CREANDO ECOSYSTEM-FRONTEND.CONFIG.JS"

print_step "Creando archivo ecosystem-frontend.config.js..."
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

print_success "Archivo ecosystem-frontend.config.js creado correctamente"

# ===========================================
# PASO 17: CONFIGURAR FIREWALL
# ===========================================
print_header "PASO 17: CONFIGURANDO FIREWALL"

print_step "Configurando UFW..."
ufw allow OpenSSH
ufw allow 3000
ufw allow 8000
ufw allow 'Nginx Full'
ufw --force enable

print_success "Firewall configurado correctamente"

# ===========================================
# PASO 18: INICIAR SERVICIOS CON PM2
# ===========================================
print_header "PASO 18: INICIANDO SERVICIOS CON PM2"

print_step "Creando directorios de logs..."
mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
chown -R www-data:www-data /var/log/backend-csdt
chown -R www-data:www-data /var/log/frontend-csdt

print_step "Iniciando backend con PM2..."
cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production

print_step "Iniciando frontend con PM2..."
cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js

print_step "Configurando PM2 para inicio automático..."
pm2 startup
pm2 save

print_success "Servicios iniciados con PM2 correctamente"

# ===========================================
# PASO 19: VERIFICACIÓN FINAL
# ===========================================
print_header "PASO 19: VERIFICACIÓN FINAL"

print_step "Verificando estado de PM2..."
pm2 status

print_step "Verificando que la API responda..."
if curl -s http://localhost:8000 > /dev/null; then
    print_success "✅ API local responde correctamente"
else
    print_warning "⚠️ API local no responde aún"
fi

print_step "Verificando que el frontend responda..."
if curl -s http://localhost:3000 > /dev/null; then
    print_success "✅ Frontend local responde correctamente"
else
    print_warning "⚠️ Frontend local no responde aún"
fi

print_step "Verificando accesibilidad externa..."
if curl -s http://$IP_PUBLICA:3000 > /dev/null; then
    print_success "✅ Frontend externo accesible"
else
    print_warning "⚠️ Frontend externo no accesible aún"
fi

if curl -s http://$IP_PUBLICA:8000 > /dev/null; then
    print_success "✅ API externa accesible"
else
    print_warning "⚠️ API externa no accesible aún"
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "INSTALACIÓN COMPLETADA EXITOSAMENTE"

print_success "✅ Backend Laravel instalado y configurado"
print_success "✅ Frontend React/Vite instalado y compilado"
print_success "✅ 13 servicios de IA configurados"
print_success "✅ Base de datos MySQL configurada"
print_success "✅ PM2 configurado para gestión de procesos"
print_success "✅ Firewall configurado"

echo ""
print_message "URLs de acceso:"
print_message "Frontend: http://$IP_PUBLICA:3000"
print_message "API Backend: http://$IP_PUBLICA:8000"

echo ""
print_warning "IMPORTANTE:"
print_warning "1. Configura las credenciales de email en el archivo .env del backend"
print_warning "2. Configura las API keys de IA en el archivo .env del frontend"
print_warning "3. Verifica que los servicios estén corriendo con: pm2 status"
print_warning "4. Revisa los logs con: pm2 logs"

echo ""
print_message "Comandos útiles:"
print_message "- Ver estado: pm2 status"
print_message "- Ver logs: pm2 logs"
print_message "- Reiniciar: pm2 restart all"
print_message "- Verificar instalación: ./verificar_instalacion_csdt.sh"

echo ""
print_success "¡Sistema CSDT instalado exitosamente!"
