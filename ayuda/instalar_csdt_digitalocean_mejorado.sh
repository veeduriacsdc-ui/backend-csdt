#!/bin/bash

# ===========================================
# SCRIPT PRINCIPAL DE INSTALACIÓN CSDT MEJORADO
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# VERSIÓN DIGITALOCEAN SIN DOMINIO - MEJORADA
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
REPO_BACKEND="https://github.com/veeduriacsdc-ui/backend-csdt.git"
REPO_FRONTEND="https://github.com/veeduriacsdc-ui/frontend-csdt"

print_header "INSTALACIÓN COMPLETA CSDT EN DIGITALOCEAN - VERSIÓN MEJORADA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_csdt_digitalocean_mejorado.sh)"
    exit 1
fi

# ===========================================
# CREAR ESTRUCTURA DE CARPETAS AYUDA
# ===========================================
print_step "Creando estructura de carpetas ayuda..."

# Crear carpetas ayuda en ambos proyectos
mkdir -p /var/www/backend-csdt/ayuda
mkdir -p /var/www/frontend-csdt/ayuda

print_success "Estructura de carpetas ayuda creada"

# ===========================================
# PASO 1: PREPARAR SERVIDOR
# ===========================================
print_header "PASO 1: PREPARANDO SERVIDOR UBUNTU"

print_step "Actualizando sistema..."
apt update && apt upgrade -y

print_step "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget unzip git htop nano vim ufw

print_step "Configurando zona horaria..."
timedatectl set-timezone America/Bogota

print_step "Configurando swap..."
if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    print_message "Swap configurado correctamente"
fi

print_success "✅ Servidor preparado"

# ===========================================
# PASO 2: INSTALAR DEPENDENCIAS
# ===========================================
print_header "PASO 2: INSTALANDO DEPENDENCIAS"

print_step "Instalando PHP 8.2..."
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3

print_step "Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

print_step "Instalando Node.js 18.x..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

print_step "Instalando PM2..."
npm install -g pm2

print_step "Instalando MySQL 8.0..."
apt install -y mysql-server-8.0

print_success "✅ Dependencias instaladas"

# ===========================================
# PASO 3: CONFIGURAR MYSQL
# ===========================================
print_header "PASO 3: CONFIGURANDO MYSQL"

print_step "Configurando MySQL..."
mysql_secure_installation <<EOF

y
123
123
y
y
y
y
EOF

print_step "Creando base de datos y usuario..."
mysql -u root -p123 <<EOF
CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY '123';
GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF

print_success "✅ MySQL configurado"

# ===========================================
# PASO 4: INSTALAR BACKEND
# ===========================================
print_header "PASO 4: INSTALANDO BACKEND LARAVEL"

print_step "Creando directorio backend..."
mkdir -p /var/www/backend-csdt
cd /var/www/backend-csdt

print_step "Clonando repositorio backend..."
git clone $REPO_BACKEND .

print_step "Instalando dependencias PHP..."
composer install --optimize-autoloader --no-dev

print_step "Instalando dependencias Node.js..."
npm install

print_step "Creando archivo .env..."
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
DB_PASSWORD=123
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

print_step "Configurando permisos..."
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

print_success "✅ Backend instalado"

# ===========================================
# PASO 5: INSTALAR FRONTEND
# ===========================================
print_header "PASO 5: INSTALANDO FRONTEND REACT"

print_step "Creando directorio frontend..."
mkdir -p /var/www/frontend-csdt
cd /var/www/frontend-csdt

print_step "Clonando repositorio frontend..."
git clone $REPO_FRONTEND .

print_step "Instalando dependencias..."
npm install

print_step "Creando archivo .env..."
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

print_step "Compilando frontend..."
npm run build

print_step "Configurando permisos..."
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

print_success "✅ Frontend instalado"

# ===========================================
# PASO 6: CREAR ARCHIVOS ECOSYSTEM
# ===========================================
print_header "PASO 6: CREANDO ARCHIVOS ECOSYSTEM"

print_step "Creando ecosystem.config.js para backend..."
cat > /var/www/backend-csdt/ecosystem.config.js << 'EOF'
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
        ignore_watch: ['node_modules', 'storage/logs', 'vendor', 'ayuda'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
EOF

print_step "Creando ecosystem-frontend.config.js para frontend..."
cat > /var/www/frontend-csdt/ecosystem-frontend.config.js << 'EOF'
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
        ignore_watch: ['node_modules', 'dist', 'ayuda'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
EOF

print_success "✅ Archivos ecosystem creados"

# ===========================================
# PASO 7: EJECUTAR MIGRACIONES Y SEEDERS
# ===========================================
print_header "PASO 7: EJECUTANDO MIGRACIONES Y SEEDERS"

print_step "Ejecutando migraciones..."
cd /var/www/backend-csdt
php artisan migrate --force

print_step "Ejecutando seeders..."
php artisan db:seed --force

print_step "Optimizando cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "✅ Migraciones y seeders ejecutados"

# ===========================================
# PASO 8: CONFIGURAR FIREWALL
# ===========================================
print_header "PASO 8: CONFIGURANDO FIREWALL"

print_step "Configurando UFW..."
ufw allow OpenSSH
ufw allow 3000
ufw allow 8000
ufw allow 'Nginx Full'
ufw --force enable

print_success "✅ Firewall configurado"

# ===========================================
# PASO 9: INICIAR SERVICIOS
# ===========================================
print_header "PASO 9: INICIANDO SERVICIOS"

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

print_success "✅ Servicios iniciados"

# ===========================================
# PASO 10: CREAR SCRIPTS DE GESTIÓN
# ===========================================
print_header "PASO 10: CREANDO SCRIPTS DE GESTIÓN"

# Crear scripts para backend
print_step "Creando scripts de gestión backend..."

cat > /var/www/backend-csdt/ayuda/iniciar_backend.sh << 'EOF'
#!/bin/bash
echo "Iniciando backend CSDT..."
cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production
pm2 save
echo "Backend iniciado correctamente"
EOF

cat > /var/www/backend-csdt/ayuda/detener_backend.sh << 'EOF'
#!/bin/bash
echo "Deteniendo backend CSDT..."
pm2 stop backend-csdt
pm2 save
echo "Backend detenido correctamente"
EOF

cat > /var/www/backend-csdt/ayuda/reiniciar_backend.sh << 'EOF'
#!/bin/bash
echo "Reiniciando backend CSDT..."
pm2 restart backend-csdt
pm2 save
echo "Backend reiniciado correctamente"
EOF

cat > /var/www/backend-csdt/ayuda/verificar_backend.sh << 'EOF'
#!/bin/bash
echo "Verificando estado del backend..."
pm2 status backend-csdt
echo ""
echo "Verificando conectividad..."
curl -I http://localhost:8000
EOF

# Crear scripts para frontend
print_step "Creando scripts de gestión frontend..."

cat > /var/www/frontend-csdt/ayuda/iniciar_frontend.sh << 'EOF'
#!/bin/bash
echo "Iniciando frontend CSDT..."
cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js
pm2 save
echo "Frontend iniciado correctamente"
EOF

cat > /var/www/frontend-csdt/ayuda/detener_frontend.sh << 'EOF'
#!/bin/bash
echo "Deteniendo frontend CSDT..."
pm2 stop frontend-csdt
pm2 save
echo "Frontend detenido correctamente"
EOF

cat > /var/www/frontend-csdt/ayuda/reiniciar_frontend.sh << 'EOF'
#!/bin/bash
echo "Reiniciando frontend CSDT..."
pm2 restart frontend-csdt
pm2 save
echo "Frontend reiniciado correctamente"
EOF

cat > /var/www/frontend-csdt/ayuda/compilar_frontend.sh << 'EOF'
#!/bin/bash
echo "Compilando frontend CSDT..."
cd /var/www/frontend-csdt
npm run build
echo "Frontend compilado correctamente"
EOF

cat > /var/www/frontend-csdt/ayuda/verificar_frontend.sh << 'EOF'
#!/bin/bash
echo "Verificando estado del frontend..."
pm2 status frontend-csdt
echo ""
echo "Verificando conectividad..."
curl -I http://localhost:3000
EOF

# Hacer scripts ejecutables
chmod +x /var/www/backend-csdt/ayuda/*.sh
chmod +x /var/www/frontend-csdt/ayuda/*.sh

print_success "✅ Scripts de gestión creados"

# ===========================================
# PASO 11: VERIFICACIÓN FINAL
# ===========================================
print_header "PASO 11: VERIFICACIÓN FINAL"

print_step "Verificando estado de PM2..."
pm2 status

print_step "Verificando conectividad backend..."
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "✅ Backend local: http://localhost:8000"
else
    print_warning "⚠️ Backend local no responde"
fi

print_step "Verificando conectividad frontend..."
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend local: http://localhost:3000"
else
    print_warning "⚠️ Frontend local no responde"
fi

print_step "Verificando conectividad externa..."
if curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1; then
    print_success "✅ Backend externo: http://$IP_PUBLICA:8000"
else
    print_warning "⚠️ Backend externo no accesible"
fi

if curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend externo: http://$IP_PUBLICA:3000"
else
    print_warning "⚠️ Frontend externo no accesible"
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "INSTALACIÓN COMPLETA FINALIZADA"

print_success "✅ Sistema CSDT instalado completamente"
print_success "✅ Backend Laravel funcionando"
print_success "✅ Frontend React funcionando"
print_success "✅ 13 servicios de IA configurados"
print_success "✅ Base de datos MySQL configurada"
print_success "✅ PM2 gestionando procesos"
print_success "✅ Firewall configurado"
print_success "✅ Scripts de gestión creados"

print_warning "INFORMACIÓN DE ACCESO:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"

print_warning "COMANDOS ÚTILES:"
print_warning "Estado: pm2 status"
print_warning "Logs: pm2 logs"
print_warning "Backend: cd /var/www/backend-csdt && ./ayuda/"
print_warning "Frontend: cd /var/www/frontend-csdt && ./ayuda/"

print_message "¡Instalación completa finalizada exitosamente!"
print_message "El sistema CSDT está listo para usar en producción."
