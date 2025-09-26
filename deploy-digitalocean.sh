#!/bin/bash

# Script de despliegue para DigitalOcean
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

echo "🚀 Iniciando despliegue en DigitalOcean..."

# Configuración del servidor
SERVER_IP="134.209.221.193"
SERVER_USER="root"
APP_NAME="csdt"
APP_PATH="/var/www/$APP_NAME"

# Verificar conexión al servidor
echo "🔍 Verificando conexión al servidor..."
ssh -o ConnectTimeout=10 $SERVER_USER@$SERVER_IP "echo 'Conexión exitosa'"

if [ $? -ne 0 ]; then
    echo "❌ Error: No se pudo conectar al servidor. Verifica la IP y credenciales."
    exit 1
fi

echo "✅ Conexión al servidor exitosa"

# Crear directorio de la aplicación si no existe
echo "📁 Creando directorio de la aplicación..."
ssh $SERVER_USER@$SERVER_IP "mkdir -p $APP_PATH"

# Sincronizar archivos del backend
echo "📤 Sincronizando archivos del backend..."
rsync -avz --delete \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude 'storage/logs' \
    --exclude 'storage/framework/cache' \
    --exclude 'storage/framework/sessions' \
    --exclude 'storage/framework/views' \
    --exclude 'bootstrap/cache' \
    --exclude '.env' \
    ./backend-csdt/ $SERVER_USER@$SERVER_IP:$APP_PATH/backend/

# Sincronizar archivos del frontend
echo "📤 Sincronizando archivos del frontend..."
rsync -avz --delete \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude 'dist' \
    ./frontend-csdt-final/ $SERVER_USER@$SERVER_IP:$APP_PATH/frontend/

# Ejecutar comandos en el servidor
echo "🔧 Configurando aplicación en el servidor..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    cd /var/www/csdt/backend
    
    # Instalar dependencias de PHP
    echo "📦 Instalando dependencias de PHP..."
    composer install --no-dev --optimize-autoloader
    
    # Configurar permisos
    echo "🔐 Configurando permisos..."
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Configurar archivo .env
    echo "⚙️  Configurando archivo .env..."
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    
    # Generar clave de aplicación
    php artisan key:generate
    
    # Ejecutar migraciones
    echo "🔄 Ejecutando migraciones..."
    php artisan migrate --force
    
    # Ejecutar seeders
    echo "🌱 Ejecutando seeders..."
    php artisan db:seed --force
    
    # Limpiar caché
    echo "🧹 Limpiando caché..."
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Configurar Nginx
    echo "🌐 Configurando Nginx..."
    cat > /etc/nginx/sites-available/csdt << 'NGINX_CONFIG'
server {
    listen 80;
    server_name tu_dominio.com www.tu_dominio.com;
    root /var/www/csdt/frontend/dist;
    index index.html;

    # Frontend (React)
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API Backend
    location /api {
        alias /var/www/csdt/backend/public;
        try_files $uri $uri/ @backend;
    }

    location @backend {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/csdt/backend/public/index.php;
        include fastcgi_params;
    }

    # Archivos estáticos
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
NGINX_CONFIG

    # Habilitar sitio
    ln -sf /etc/nginx/sites-available/csdt /etc/nginx/sites-enabled/
    nginx -t && systemctl reload nginx
    
    # Configurar PHP-FPM
    echo "🐘 Configurando PHP-FPM..."
    systemctl restart php8.1-fpm
    
    # Configurar SSL con Let's Encrypt (opcional)
    echo "🔒 Configurando SSL..."
    # certbot --nginx -d tu_dominio.com -d www.tu_dominio.com
    
    echo "✅ Configuración del servidor completada"
EOF

# Construir frontend en el servidor
echo "🏗️  Construyendo frontend en el servidor..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    cd /var/www/csdt/frontend
    
    # Instalar dependencias de Node.js
    echo "📦 Instalando dependencias de Node.js..."
    npm install
    
    # Construir aplicación
    echo "🏗️  Construyendo aplicación..."
    npm run build
    
    # Configurar permisos
    chown -R www-data:www-data dist/
    chmod -R 755 dist/
    
    echo "✅ Frontend construido exitosamente"
EOF

# Verificar estado de los servicios
echo "🔍 Verificando estado de los servicios..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    echo "📊 Estado de Nginx:"
    systemctl status nginx --no-pager -l
    
    echo "📊 Estado de PHP-FPM:"
    systemctl status php8.1-fpm --no-pager -l
    
    echo "📊 Estado de la aplicación:"
    curl -s http://localhost/api/health || echo "API no disponible"
EOF

echo "🎉 Despliegue completado exitosamente!"
echo "📋 Resumen:"
echo "   - Backend desplegado en: $APP_PATH/backend"
echo "   - Frontend desplegado en: $APP_PATH/frontend"
echo "   - Nginx configurado"
echo "   - PHP-FPM configurado"
echo "   - Base de datos migrada"
echo ""
echo "🌐 Accede a tu aplicación en: http://tu_dominio.com"
echo "🔧 API disponible en: http://tu_dominio.com/api"
