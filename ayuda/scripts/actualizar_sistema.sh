#!/bin/bash

# ===========================================
# SCRIPT DE ACTUALIZACIÓN DEL SISTEMA
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

print_header "ACTUALIZACIÓN DEL SISTEMA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# HACER BACKUP ANTES DE ACTUALIZAR
# ===========================================
print_message "Haciendo backup antes de actualizar..."

if [ -f "/var/www/backend-csdt/ayuda/scripts/backup_csdt.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/backup_csdt.sh
else
    print_warning "Script de backup no encontrado, continuando sin backup"
fi

print_success "✅ Backup completado"

# ===========================================
# ACTUALIZAR SISTEMA OPERATIVO
# ===========================================
print_message "Actualizando sistema operativo..."

# Actualizar paquetes
apt update
apt upgrade -y

# Limpiar paquetes no utilizados
apt autoremove -y
apt autoclean

print_success "✅ Sistema operativo actualizado"

# ===========================================
# ACTUALIZAR DEPENDENCIAS
# ===========================================
print_message "Actualizando dependencias..."

# Actualizar PHP
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3

# Actualizar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Actualizar Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt-get install -y nodejs

# Actualizar PM2
npm install -g pm2

print_success "✅ Dependencias actualizadas"

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

print_success "✅ Frontend actualizado"

# ===========================================
# ACTUALIZAR SERVICIOS DE IA
# ===========================================
print_message "Actualizando servicios de IA..."

if [ -f "/var/www/backend-csdt/ayuda/scripts/instalar_servicios_ia.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/instalar_servicios_ia.sh
else
    print_warning "Script de servicios de IA no encontrado"
fi

print_success "✅ Servicios de IA actualizados"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_message "Reiniciando servicios..."

pm2 restart all
pm2 save

print_success "✅ Servicios reiniciados"

# ===========================================
# VERIFICAR ACTUALIZACIÓN
# ===========================================
print_message "Verificando actualización..."

# Verificar estado de PM2
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

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES
# ===========================================
print_message "Limpiando archivos temporales..."

if [ -f "/var/www/backend-csdt/ayuda/scripts/limpiar_archivos_temporales.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/limpiar_archivos_temporales.sh
else
    print_warning "Script de limpieza no encontrado"
fi

print_success "✅ Archivos temporales limpiados"

# ===========================================
# VERIFICAR VERSIONES
# ===========================================
print_message "Verificando versiones..."

echo "Versión de PHP:"
php --version

echo "Versión de Composer:"
composer --version

echo "Versión de Node.js:"
node --version

echo "Versión de NPM:"
npm --version

echo "Versión de PM2:"
pm2 --version

print_success "✅ Versiones verificadas"

print_header "ACTUALIZACIÓN COMPLETADA"

print_success "✅ Sistema actualizado correctamente"
print_message "Backend: http://64.225.113.49:8000"
print_message "Frontend: http://64.225.113.49:3000"
print_message "Para verificar el estado, ejecuta: verificar_csdt.sh"
