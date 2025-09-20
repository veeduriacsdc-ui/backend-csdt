#!/bin/bash

# 🚀 SCRIPT DE DESPLIEGUE AUTOMATIZADO
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# Para DigitalOcean Droplet

echo "🚀 Iniciando despliegue automático de CSDT Veeduría..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración del servidor
IP_PUBLICA="143.198.181.113"
IP_PRIVADA="10.116.0.2"
BACKEND_REPO="https://github.com/veeduriacsdc-ui/backend-csdt.git"
FRONTEND_REPO="https://github.com/veeduriacsdc-ui/frontend-csdt.git"

# Función para mostrar progreso
show_progress() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

show_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

show_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

show_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Función para verificar si un comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# PASO 1: Actualizar sistema
show_progress "Actualizando sistema operativo..."
apt update && apt upgrade -y
show_success "Sistema actualizado"

# PASO 2: Instalar dependencias básicas
show_progress "Instalando dependencias básicas..."
apt install -y curl wget git unzip software-properties-common
show_success "Dependencias básicas instaladas"

# PASO 3: Instalar Node.js
show_progress "Instalando Node.js 20.x..."
if ! command_exists node; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    apt install -y nodejs
    show_success "Node.js $(node --version) instalado"
else
    show_warning "Node.js ya está instalado: $(node --version)"
fi

# PASO 4: Instalar PHP 8.2
show_progress "Instalando PHP 8.2 y extensiones..."
if ! command_exists php; then
    add-apt-repository ppa:ondrej/php -y
    apt update
    apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-sqlite3 php8.2-bcmath
    show_success "PHP 8.2 instalado"
else
    show_warning "PHP ya está instalado: $(php --version | head -n1)"
fi

# PASO 5: Instalar Composer
show_progress "Instalando Composer..."
if ! command_exists composer; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
    show_success "Composer instalado"
else
    show_warning "Composer ya está instalado"
fi

# PASO 6: Instalar Nginx
show_progress "Instalando Nginx..."
if ! command_exists nginx; then
    apt install -y nginx
    systemctl enable nginx
    systemctl start nginx
    show_success "Nginx instalado y iniciado"
else
    show_warning "Nginx ya está instalado"
fi

# PASO 6.1: Instalar Python y herramientas de IA
show_progress "Instalando herramientas de IA y Machine Learning..."
apt install -y python3.11 python3.11-pip python3.11-venv python3.11-dev
apt install -y build-essential libssl-dev libffi-dev libxml2-dev libxslt1-dev zlib1g-dev
pip3 install --upgrade pip
pip3 install openai==1.3.0 transformers==4.35.0 torch==2.1.0 sentence-transformers==2.2.2
pip3 install flask==2.3.3 fastapi==0.104.1 uvicorn==0.24.0
show_success "Herramientas de IA instaladas"

# PASO 7: Configurar firewall
show_progress "Configurando firewall..."
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 5173/tcp
ufw --force enable
show_success "Firewall configurado"

# PASO 8: Crear directorios del proyecto
show_progress "Creando directorios del proyecto..."
mkdir -p /var/www/backend-csdt
mkdir -p /var/www/frontend-csdt
show_success "Directorios creados"

# PASO 9: Clonar repositorio backend
show_progress "Clonando repositorio backend..."
cd /var/www/backend-csdt
if [ ! -d ".git" ]; then
    git clone $BACKEND_REPO .
    show_success "Backend clonado desde GitHub"
else
    show_warning "Backend ya está clonado, actualizando..."
    git pull origin main
fi

# PASO 10: Clonar repositorio frontend
show_progress "Clonando repositorio frontend..."
cd /var/www/frontend-csdt
if [ ! -d ".git" ]; then
    git clone $FRONTEND_REPO .
    show_success "Frontend clonado desde GitHub"
else
    show_warning "Frontend ya está clonado, actualizando..."
    git pull origin main
fi

# PASO 11: Instalar dependencias backend
show_progress "Instalando dependencias del backend..."
cd /var/www/backend-csdt
composer install --optimize-autoloader --no-dev
if [ -f "package.json" ]; then
    npm install --production
fi
show_success "Dependencias del backend instaladas"

# PASO 12: Instalar dependencias frontend
show_progress "Instalando dependencias del frontend..."
cd /var/www/frontend-csdt
npm install
show_success "Dependencias del frontend instaladas"

# PASO 13: Configurar archivo .env backend
show_progress "Configurando archivo .env del backend..."
cd /var/www/backend-csdt
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        cat > .env << EOF
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=production
APP_KEY=base64:rawkmxypkwrwoexu
APP_DEBUG=false
APP_TIMEZONE=America/Bogota
APP_URL=http://$IP_PUBLICA:8000

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_final
DB_USERNAME=csdt
DB_PASSWORD=rawkmxypkwrwoexu
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_COOKIE_NAME=csdt_session
SESSION_COOKIE_PATH=/
SESSION_COOKIE_DOMAIN=$IP_PUBLICA

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=csdtjusticia@gmail.com
MAIL_PASSWORD="rawkmxypkwrwoexu"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=csdtjusticia@gmail.com
MAIL_FROM_NAME="\${APP_NAME}"

SANCTUM_STATEFUL_DOMAINS=$IP_PUBLICA:3000,$IP_PUBLICA:8000
SESSION_DOMAIN=$IP_PUBLICA
SANCTUM_GUARD=web
SANCTUM_MIDDLEWARE=throttle:api

CORS_ALLOWED_ORIGINS=http://$IP_PUBLICA:3000,http://localhost:3000
CORS_ALLOWED_HEADERS=Accept,Authorization,Content-Type,X-Requested-With,X-CSRF-TOKEN,X-XSRF-TOKEN
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,OPTIONS,PATCH
CORS_SUPPORTS_CREDENTIALS=true
CORS_MAX_AGE=86400
CORS_EXPOSED_HEADERS=Authorization

LOG_CHANNEL=stack
LOG_LEVEL=info
LOG_DEPRECATIONS_CHANNEL=null

UPLOAD_PATH=/var/www/backend-csdt/storage/uploads
MAX_FILE_SIZE=10485760
ALLOWED_FILE_TYPES=jpg,jpeg,png,pdf,doc,docx

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

OPENAI_API_KEY=
ANTHROPIC_API_KEY=

SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

BCRYPT_ROUNDS=12

VITE_APP_NAME="\${APP_NAME}"
VITE_APP_ENV=production
EOF
    fi
    php artisan key:generate
    show_success "Archivo .env del backend configurado"
else
    show_warning "Archivo .env del backend ya existe"
fi

# PASO 14: Configurar archivo .env frontend
show_progress "Configurando archivo .env del frontend..."
cd /var/www/frontend-csdt
cat > .env << EOF
VITE_API_URL=http://$IP_PUBLICA/api
VITE_APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
VITE_APP_ENV=production
EOF
show_success "Archivo .env del frontend configurado"

# PASO 15: Configurar base de datos backend
show_progress "Configurando base de datos..."
cd /var/www/backend-csdt
touch database/database.sqlite
chmod 664 database/database.sqlite
chown www-data:www-data database/database.sqlite
php artisan migrate --force
show_success "Base de datos configurada"

# PASO 16: Construir frontend
show_progress "Construyendo frontend para producción..."
cd /var/www/frontend-csdt
npm run build
show_success "Frontend construido"

# PASO 17: Configurar Nginx backend
show_progress "Configurando Nginx para backend..."
cat > /etc/nginx/sites-available/backend-csdt << EOF
server {
    listen 80;
    server_name $IP_PUBLICA;
    root /var/www/backend-csdt/public;
    index index.php index.html index.htm;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    access_log /var/log/nginx/backend-csdt-access.log;
    error_log /var/log/nginx/backend-csdt-error.log;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
}
EOF
ln -sf /etc/nginx/sites-available/backend-csdt /etc/nginx/sites-enabled/
show_success "Nginx backend configurado"

# PASO 18: Configurar Nginx frontend
show_progress "Configurando Nginx para frontend..."
cat > /etc/nginx/sites-available/frontend-csdt << EOF
server {
    listen 5173;
    server_name $IP_PUBLICA;
    root /var/www/frontend-csdt/dist;
    index index.html;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:80;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    access_log /var/log/nginx/frontend-csdt-access.log;
    error_log /var/log/nginx/frontend-csdt-error.log;
}
EOF
ln -sf /etc/nginx/sites-available/frontend-csdt /etc/nginx/sites-enabled/
show_success "Nginx frontend configurado"

# PASO 19: Configurar permisos
show_progress "Configurando permisos de archivos..."
chown -R www-data:www-data /var/www/backend-csdt
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 755 /var/www/frontend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache
show_success "Permisos configurados"

# PASO 20: Reiniciar servicios
show_progress "Reiniciando servicios..."
systemctl restart php8.2-fpm
systemctl reload nginx
show_success "Servicios reiniciados"

# PASO 21: Optimizar Laravel
show_progress "Optimizando Laravel..."
cd /var/www/backend-csdt
php artisan config:cache
php artisan route:cache
php artisan view:cache
show_success "Laravel optimizado"

# PASO 22: Verificación final
show_progress "Verificando despliegue..."
nginx -t
if [ $? -eq 0 ]; then
    show_success "Configuración de Nginx válida"
else
    show_error "Error en configuración de Nginx"
fi

# Verificar servicios
systemctl is-active --quiet nginx && show_success "Nginx activo" || show_error "Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && show_success "PHP-FPM activo" || show_error "PHP-FPM inactivo"

# PASO 23: Mostrar información final
echo ""
echo "🎉 ¡DESPLIEGUE COMPLETADO EXITOSAMENTE!"
echo "=========================================="
echo ""
echo "📊 INFORMACIÓN DEL SERVIDOR:"
echo "   IP Pública: $IP_PUBLICA"
echo "   IP Privada: $IP_PRIVADA"
echo ""
echo "🌐 URLs DE ACCESO:"
echo "   Frontend: http://$IP_PUBLICA:5173"
echo "   Backend API: http://$IP_PUBLICA/api"
echo ""
echo "📁 DIRECTORIOS:"
echo "   Backend: /var/www/backend-csdt"
echo "   Frontend: /var/www/frontend-csdt"
echo ""
echo "📝 LOGS:"
echo "   Backend: /var/log/nginx/backend-csdt-*.log"
echo "   Frontend: /var/log/nginx/frontend-csdt-*.log"
echo "   Laravel: /var/www/backend-csdt/storage/logs/laravel.log"
echo ""
echo "🔧 COMANDOS ÚTILES:"
echo "   Ver logs: tail -f /var/log/nginx/backend-csdt-error.log"
echo "   Reiniciar Nginx: systemctl reload nginx"
echo "   Reiniciar PHP-FPM: systemctl restart php8.2-fpm"
echo ""
echo "✅ Tu aplicación CSDT Veeduría está lista para usar!"
