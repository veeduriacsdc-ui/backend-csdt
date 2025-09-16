#!/bin/bash

# Script de despliegue para CSDT - Backend
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

echo "🚀 Iniciando despliegue del backend CSDT..."

# Verificar si estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: Debes ejecutar este script desde el directorio raíz del proyecto Laravel"
    exit 1
fi

# Instalar dependencias de PHP
echo "📦 Instalando dependencias de PHP..."
composer install --no-dev --optimize-autoloader

# Generar clave de aplicación si no existe
if [ -z "$APP_KEY" ]; then
    echo "🔑 Generando clave de aplicación..."
    php artisan key:generate
fi

# Ejecutar migraciones
echo "🗄️ Ejecutando migraciones..."
php artisan migrate --force

# Ejecutar seeders
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force

# Limpiar y optimizar
echo "🧹 Limpiando caché..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
echo "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Establecer permisos
echo "🔒 Estableciendo permisos..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 755 storage bootstrap/cache

# Instalar dependencias de Node.js y construir assets
echo "📦 Instalando dependencias de Node.js..."
npm install

echo "🏗️ Construyendo assets..."
npm run build

# Crear enlace simbólico para storage
echo "🔗 Creando enlace simbólico para storage..."
php artisan storage:link

echo "✅ Despliegue completado exitosamente!"
echo ""
echo "📋 Próximos pasos:"
echo "1. Configurar servidor web (Nginx/Apache)"
echo "2. Configurar SSL/TLS"
echo "3. Configurar base de datos de producción"
echo "4. Configurar variables de entorno"
echo "5. Probar la aplicación"
echo ""
echo "🌐 URL del backend: $(grep APP_URL .env | cut -d '=' -f2)"
