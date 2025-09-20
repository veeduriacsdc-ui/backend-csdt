#!/bin/bash

# ===========================================
# SCRIPT DE REPARACIÓN CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_header "REPARACIÓN CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# DETENER SERVICIOS
# ===========================================
print_message "Deteniendo servicios..."
pm2 stop all

# ===========================================
# REPARAR BACKEND
# ===========================================
print_message "Reparando backend..."

cd /var/www/backend-csdt

# Reparar permisos
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Reinstalar dependencias
composer install --optimize-autoloader --no-dev
npm install

# Ejecutar migraciones
php artisan migrate --force

# Optimizar cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "✅ Backend reparado"

# ===========================================
# REPARAR FRONTEND
# ===========================================
print_message "Reparando frontend..."

cd /var/www/frontend-csdt

# Reparar permisos
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

# Reinstalar dependencias
npm install

# Compilar
npm run build

print_success "✅ Frontend reparado"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_message "Reiniciando servicios..."

cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production

cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js

pm2 save

# ===========================================
# VERIFICACIÓN
# ===========================================
print_message "Verificando servicios..."

sleep 10

pm2 status

# Verificar conectividad
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "✅ Backend funcionando"
else
    print_warning "⚠️ Backend no responde"
fi

if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend funcionando"
else
    print_warning "⚠️ Frontend no responde"
fi

print_header "REPARACIÓN COMPLETADA"

print_success "✅ Sistema CSDT reparado correctamente"
print_warning "Backend: http://64.225.113.49:8000"
print_warning "Frontend: http://64.225.113.49:3000"
