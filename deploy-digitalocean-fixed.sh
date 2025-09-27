#!/bin/bash

# Script de despliegue corregido para DigitalOcean
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

echo "🚀 Iniciando despliegue corregido en DigitalOcean..."

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

# Ejecutar comandos en el servidor
echo "🔧 Configurando aplicación en el servidor..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    # Crear directorio de la aplicación si no existe
    mkdir -p /var/www/csdt/{backend,frontend}
    
    # Actualizar sistema
    apt update && apt upgrade -y
    
    # Instalar dependencias necesarias
    apt install -y nginx php8.1-fpm php8.1-cli php8.1-mysql php8.1-xml php8.1-mbstring php8.1-curl php8.1-zip php8.1-bcmath php8.1-intl php8.1-gd php8.1-sqlite3 composer nodejs npm
    
    # Configurar PHP-FPM
    systemctl enable php8.1-fpm
    systemctl start php8.1-fpm
    
    # Configurar Nginx
    systemctl enable nginx
    systemctl start nginx
    
    echo "✅ Servicios instalados y configurados"
EOF

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
    ./ $SERVER_USER@$SERVER_IP:$APP_PATH/backend/

# Sincronizar archivos del frontend
echo "📤 Sincronizando archivos del frontend..."
rsync -avz --delete \
    --exclude 'node_modules' \
    --exclude '.git' \
    --exclude 'dist' \
    ../frontend-csdt-final/ $SERVER_USER@$SERVER_IP:$APP_PATH/frontend/

# Configurar backend en el servidor
echo "🔧 Configurando backend en el servidor..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    cd /var/www/csdt/backend
    
    # Instalar dependencias de PHP
    echo "📦 Instalando dependencias de PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction
    
    # Configurar permisos
    echo "🔐 Configurando permisos..."
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    
    # Configurar archivo .env
    echo "⚙️  Configurando archivo .env..."
    if [ ! -f .env ]; then
        cp .env.example .env
    fi
    
    # Configurar base de datos para producción
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
    sed -i 's/DB_DATABASE=database\/database.sqlite/DB_DATABASE=csdt_production/' .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=localhost/' .env
    sed -i 's/DB_USERNAME=root/DB_USERNAME=csdt_user/' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=csdt_password_2024/' .env
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    sed -i 's/APP_URL=http:\/\/localhost:8000/APP_URL=http:\/\/134.209.221.193/' .env
    
    # Generar clave de aplicación
    php artisan key:generate
    
    # Configurar base de datos MySQL
    echo "🗄️  Configurando base de datos MySQL..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS csdt_production;"
    mysql -u root -e "CREATE USER IF NOT EXISTS 'csdt_user'@'localhost' IDENTIFIED BY 'csdt_password_2024';"
    mysql -u root -e "GRANT ALL PRIVILEGES ON csdt_production.* TO 'csdt_user'@'localhost';"
    mysql -u root -e "FLUSH PRIVILEGES;"
    
    # Ejecutar migraciones
    echo "🔄 Ejecutando migraciones..."
    php artisan migrate --force
    
    # Limpiar caché
    echo "🧹 Limpiando caché..."
    php artisan cache:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Crear enlace simbólico de storage
    php artisan storage:link
    
    echo "✅ Backend configurado exitosamente"
EOF

# Configurar frontend en el servidor
echo "🏗️  Configurando frontend en el servidor..."
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

# Configurar Nginx
echo "🌐 Configurando Nginx..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    # Crear configuración de Nginx
    cat > /etc/nginx/sites-available/csdt << 'NGINX_CONFIG'
server {
    listen 80;
    server_name 134.209.221.193;
    root /var/www/csdt/frontend/dist;
    index index.html;

    # Configuración de logs
    access_log /var/log/nginx/csdt_access.log;
    error_log /var/log/nginx/csdt_error.log;

    # Frontend (React) - Servir archivos estáticos
    location / {
        try_files $uri $uri/ /index.html;
        
        # Headers de seguridad
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
        add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    }

    # API Backend - Proxy a Laravel
    location /api {
        alias /var/www/csdt/backend/public;
        try_files $uri $uri/ @backend;
    }

    location @backend {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME /var/www/csdt/backend/public/index.php;
        fastcgi_param REQUEST_URI $request_uri;
        fastcgi_param QUERY_STRING $query_string;
        fastcgi_param REQUEST_METHOD $request_method;
        fastcgi_param CONTENT_TYPE $content_type;
        fastcgi_param CONTENT_LENGTH $content_length;
        fastcgi_param SERVER_NAME $server_name;
        fastcgi_param SERVER_PORT $server_port;
        fastcgi_param HTTPS $https if_not_empty;
        include fastcgi_params;
    }

    # Archivos estáticos del backend
    location ~ ^/api/(.*\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot))$ {
        alias /var/www/csdt/backend/public/$1;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Archivos estáticos del frontend
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Configuración de CORS para API
    location /api {
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
        
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, DELETE, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        alias /var/www/csdt/backend/public;
        try_files $uri $uri/ @backend;
    }

    # Configuración de seguridad
    location ~ /\. {
        deny all;
    }

    # Configuración de PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Configuración de compresión
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
}
NGINX_CONFIG

    # Habilitar sitio
    ln -sf /etc/nginx/sites-available/csdt /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Verificar configuración de Nginx
    nginx -t
    
    if [ $? -eq 0 ]; then
        systemctl reload nginx
        echo "✅ Nginx configurado y recargado exitosamente"
    else
        echo "❌ Error en la configuración de Nginx"
        exit 1
    fi
EOF

# Verificar estado de los servicios
echo "🔍 Verificando estado de los servicios..."
ssh $SERVER_USER@$SERVER_IP << 'EOF'
    echo "📊 Estado de Nginx:"
    systemctl status nginx --no-pager -l
    
    echo "📊 Estado de PHP-FPM:"
    systemctl status php8.1-fpm --no-pager -l
    
    echo "📊 Estado de MySQL:"
    systemctl status mysql --no-pager -l
    
    echo "📊 Verificando API:"
    curl -s http://localhost/api/health || echo "API no disponible"
    
    echo "📊 Verificando Frontend:"
    curl -s http://localhost/ | head -5
EOF

echo "🎉 Despliegue completado exitosamente!"
echo "📋 Resumen:"
echo "   - Backend desplegado en: $APP_PATH/backend"
echo "   - Frontend desplegado en: $APP_PATH/frontend"
echo "   - Nginx configurado con proxy reverso"
echo "   - PHP-FPM configurado"
echo "   - MySQL configurado"
echo "   - Base de datos migrada"
echo ""
echo "🌐 Accede a tu aplicación en: http://134.209.221.193"
echo "🔧 API disponible en: http://134.209.221.193/api"
