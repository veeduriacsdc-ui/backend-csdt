#!/bin/bash

# ===========================================
# SCRIPT DE ACTUALIZACIÓN COMPLETA CSDT
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

print_header "ACTUALIZACIÓN COMPLETA CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# ACTUALIZAR BACKEND
# ===========================================
print_message "Actualizando backend..."

cd /var/www/backend-csdt

# Hacer backup de .env
cp .env .env.backup

# Actualizar código
git pull origin main

# Restaurar .env
cp .env.backup .env

# Instalar dependencias
composer install --optimize-autoloader --no-dev
npm install

# Ejecutar migraciones
php artisan migrate --force

# Limpiar y optimizar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Reiniciar backend
pm2 restart backend-csdt

print_success "✅ Backend actualizado"

# ===========================================
# ACTUALIZAR FRONTEND
# ===========================================
print_message "Actualizando frontend..."

cd /var/www/frontend-csdt

# Hacer backup de .env
cp .env .env.backup

# Actualizar código
git pull origin main

# Restaurar .env
cp .env.backup .env

# Instalar dependencias
npm install

# Compilar
npm run build

# Reiniciar frontend
pm2 restart frontend-csdt

print_success "✅ Frontend actualizado"

# ===========================================
# VERIFICACIÓN
# ===========================================
print_message "Verificando servicios..."

pm2 status

print_header "ACTUALIZACIÓN COMPLETADA"

print_success "✅ Sistema CSDT actualizado correctamente"
print_warning "Backend: http://64.225.113.49:8000"
print_warning "Frontend: http://64.225.113.49:3000"
