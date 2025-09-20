#!/bin/bash

# ===========================================
# SCRIPT DE LIMPIEZA Y OPTIMIZACIÓN CSDT
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

print_header "LIMPIEZA Y OPTIMIZACIÓN CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# LIMPIAR CACHE DEL SISTEMA
# ===========================================
print_message "Limpiando cache del sistema..."
apt clean
apt autoremove -y

# ===========================================
# LIMPIAR BACKEND
# ===========================================
print_message "Limpiando backend..."

cd /var/www/backend-csdt

# Limpiar cache de Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Limpiar logs antiguos
find /var/www/backend-csdt/storage/logs -name "*.log" -mtime +30 -delete

# Limpiar archivos temporales
find /var/www/backend-csdt -name "*.tmp" -delete
find /var/www/backend-csdt -name "*.temp" -delete

print_success "✅ Backend limpiado"

# ===========================================
# LIMPIAR FRONTEND
# ===========================================
print_message "Limpiando frontend..."

cd /var/www/frontend-csdt

# Limpiar node_modules y reinstalar
rm -rf node_modules
npm install

# Recompilar
npm run build

print_success "✅ Frontend limpiado"

# ===========================================
# LIMPIAR LOGS
# ===========================================
print_message "Limpiando logs..."

# Limpiar logs antiguos
find /var/log/backend-csdt -name "*.log" -mtime +30 -delete
find /var/log/frontend-csdt -name "*.log" -mtime +30 -delete

# Limpiar logs del sistema
journalctl --vacuum-time=30d

print_success "✅ Logs limpiados"

# ===========================================
# OPTIMIZAR BASE DE DATOS
# ===========================================
print_message "Optimizando base de datos..."

cd /var/www/backend-csdt

# Optimizar tablas
php artisan tinker --execute="
DB::statement('OPTIMIZE TABLE users');
DB::statement('OPTIMIZE TABLE password_reset_tokens');
DB::statement('OPTIMIZE TABLE failed_jobs');
DB::statement('OPTIMIZE TABLE personal_access_tokens');
DB::statement('OPTIMIZE TABLE migrations');
DB::statement('OPTIMIZE TABLE sessions');
echo 'Base de datos optimizada';
"

print_success "✅ Base de datos optimizada"

# ===========================================
# OPTIMIZAR CACHE
# ===========================================
print_message "Optimizando cache..."

cd /var/www/backend-csdt

# Optimizar cache de Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "✅ Cache optimizado"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_message "Reiniciando servicios..."

pm2 restart all
pm2 save

print_success "✅ Servicios reiniciados"

# ===========================================
# VERIFICAR ESPACIO EN DISCO
# ===========================================
print_message "Verificando espacio en disco..."

echo "Espacio disponible:"
df -h /

echo "Tamaño de directorios principales:"
du -sh /var/www/backend-csdt
du -sh /var/www/frontend-csdt
du -sh /var/log/backend-csdt
du -sh /var/log/frontend-csdt

print_header "LIMPIEZA COMPLETADA"

print_success "✅ Sistema CSDT limpiado y optimizado"
print_message "Espacio liberado y sistema optimizado"
