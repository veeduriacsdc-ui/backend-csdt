#!/bin/bash

# ===========================================
# SCRIPT 4: INSTALAR BACKEND
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

print_step() {
    echo -e "${GREEN}[PASO]${NC} $1"
}

# Variables de configuración
REPO_BACKEND="https://github.com/veeduriacsdc-ui/backend-csdt.git"
BACKEND_DIR="/var/www/backend-csdt"
IP_PUBLICA="64.225.113.49"
DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"

print_header "PASO 4: INSTALANDO BACKEND LARAVEL"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./04_instalar_backend.sh)"
    exit 1
fi

# ===========================================
# CREAR DIRECTORIO DEL BACKEND
# ===========================================
print_step "Creando directorio del backend..."

# Limpiar directorio si existe
if [ -d "$BACKEND_DIR" ]; then
    print_warning "Directorio $BACKEND_DIR ya existe, limpiando..."
    rm -rf "$BACKEND_DIR"
fi

mkdir -p "$BACKEND_DIR"
cd "$BACKEND_DIR"

print_message "✅ Directorio creado: $BACKEND_DIR"

# ===========================================
# CLONAR REPOSITORIO DEL BACKEND
# ===========================================
print_step "Clonando repositorio del backend..."

# Clonar repositorio
git clone "$REPO_BACKEND" .

# Verificar que se clonó correctamente
if [ -f "artisan" ]; then
    print_message "✅ Repositorio clonado correctamente"
else
    print_error "❌ Error clonando repositorio"
    exit 1
fi

# ===========================================
# INSTALAR DEPENDENCIAS PHP
# ===========================================
print_step "Instalando dependencias PHP (23 paquetes)..."

# Instalar dependencias PHP
composer install --optimize-autoloader --no-dev --no-interaction

# Verificar instalación
if [ -d "vendor" ]; then
    VENDOR_COUNT=$(ls vendor/ | wc -l)
    print_message "✅ Dependencias PHP instaladas: $VENDOR_COUNT paquetes"
else
    print_error "❌ Error instalando dependencias PHP"
    exit 1
fi

# ===========================================
# INSTALAR DEPENDENCIAS NODE.JS
# ===========================================
print_step "Instalando dependencias Node.js (38 paquetes)..."

# Instalar dependencias Node.js
npm install --silent

# Verificar instalación
if [ -d "node_modules" ]; then
    NODE_COUNT=$(ls node_modules/ | wc -l)
    print_message "✅ Dependencias Node.js instaladas: $NODE_COUNT paquetes"
else
    print_error "❌ Error instalando dependencias Node.js"
    exit 1
fi

# ===========================================
# CREAR ARCHIVO .ENV
# ===========================================
print_step "Creando archivo .env para el backend..."

cat > .env << EOF
# ===========================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ===========================================
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://$IP_PUBLICA:8000
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
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD
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
SESSION_COOKIE_DOMAIN=$IP_PUBLICA

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
MAIL_FROM_NAME="\${APP_NAME}"

# ===========================================
# CONFIGURACIÓN DE SANCTUM
# ===========================================
SANCTUM_STATEFUL_DOMAINS=$IP_PUBLICA:3000,$IP_PUBLICA:8000
SESSION_DOMAIN=$IP_PUBLICA
SANCTUM_GUARD=web
SANCTUM_MIDDLEWARE=throttle:api

# ===========================================
# CONFIGURACIÓN DE CORS
# ===========================================
CORS_ALLOWED_ORIGINS=http://$IP_PUBLICA:3000,http://localhost:3000
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
UPLOAD_PATH=$BACKEND_DIR/storage/uploads
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx

# ===========================================
# CONFIGURACIÓN DE REDIS
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
VITE_APP_NAME="\${APP_NAME}"
VITE_APP_ENV=production
EOF

print_message "✅ Archivo .env creado correctamente"

# ===========================================
# GENERAR CLAVE DE APLICACIÓN
# ===========================================
print_step "Generando clave de aplicación..."

# Generar clave de aplicación
php artisan key:generate --force

# Verificar que se generó la clave
if grep -q "APP_KEY=base64:" .env; then
    print_message "✅ Clave de aplicación generada correctamente"
else
    print_error "❌ Error generando clave de aplicación"
    exit 1
fi

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_step "Configurando permisos..."

# Configurar permisos
chown -R www-data:www-data "$BACKEND_DIR"
chmod -R 755 "$BACKEND_DIR"
chmod -R 775 "$BACKEND_DIR/storage"
chmod -R 775 "$BACKEND_DIR/bootstrap/cache"

print_message "✅ Permisos configurados correctamente"

# ===========================================
# CREAR DIRECTORIOS NECESARIOS
# ===========================================
print_step "Creando directorios necesarios..."

# Crear directorios de storage
mkdir -p "$BACKEND_DIR/storage/app/public"
mkdir -p "$BACKEND_DIR/storage/framework/cache"
mkdir -p "$BACKEND_DIR/storage/framework/sessions"
mkdir -p "$BACKEND_DIR/storage/framework/views"
mkdir -p "$BACKEND_DIR/storage/logs"
mkdir -p "$BACKEND_DIR/storage/uploads"

# Crear directorios de bootstrap
mkdir -p "$BACKEND_DIR/bootstrap/cache"

# Configurar permisos de storage
chmod -R 775 "$BACKEND_DIR/storage"
chmod -R 775 "$BACKEND_DIR/bootstrap/cache"

print_message "✅ Directorios creados correctamente"

# ===========================================
# CREAR ARCHIVO ECOSYSTEM.CONFIG.JS
# ===========================================
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

print_message "✅ Archivo ecosystem.config.js creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_step "Verificando configuración del backend..."

# Verificar que Laravel funciona
if php artisan --version > /dev/null 2>&1; then
    LARAVEL_VERSION=$(php artisan --version | cut -d' ' -f3)
    print_message "✅ Laravel $LARAVEL_VERSION funcionando correctamente"
else
    print_error "❌ Error con Laravel"
    exit 1
fi

# Verificar conexión a la base de datos
if php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    print_message "✅ Conexión a la base de datos exitosa"
else
    print_warning "⚠️ No se puede conectar a la base de datos (normal si no se han ejecutado las migraciones)"
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "BACKEND INSTALADO EXITOSAMENTE"

print_message "✅ Repositorio clonado correctamente"
print_message "✅ Dependencias PHP instaladas (23 paquetes)"
print_message "✅ Dependencias Node.js instaladas (38 paquetes)"
print_message "✅ Archivo .env configurado"
print_message "✅ Clave de aplicación generada"
print_message "✅ Permisos configurados"
print_message "✅ Directorios creados"
print_message "✅ Archivo ecosystem.config.js creado"

print_warning "INFORMACIÓN DEL BACKEND:"
print_warning "Directorio: $BACKEND_DIR"
print_warning "URL: http://$IP_PUBLICA:8000"
print_warning "Base de datos: $DB_NAME"
print_warning "Usuario: $DB_USER"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./05_instalar_frontend.sh"

print_message "¡Backend instalado correctamente!"
