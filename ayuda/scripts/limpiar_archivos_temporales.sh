#!/bin/bash

# ===========================================
# SCRIPT DE LIMPIEZA DE ARCHIVOS TEMPORALES
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

print_header "LIMPIEZA DE ARCHIVOS TEMPORALES"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES DEL SISTEMA
# ===========================================
print_message "Limpiando archivos temporales del sistema..."

# Limpiar cache de APT
apt clean
apt autoremove -y

# Limpiar archivos temporales
rm -rf /tmp/*
rm -rf /var/tmp/*

# Limpiar cache de usuario
rm -rf /root/.cache/*
rm -rf /home/*/.cache/*

print_success "✅ Archivos temporales del sistema limpiados"

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES DEL BACKEND
# ===========================================
print_message "Limpiando archivos temporales del backend..."

cd /var/www/backend-csdt

# Limpiar cache de Laravel
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Limpiar archivos temporales
find . -name "*.tmp" -delete
find . -name "*.temp" -delete
find . -name "*.log" -mtime +30 -delete

# Limpiar logs antiguos
find storage/logs -name "*.log" -mtime +30 -delete

print_success "✅ Archivos temporales del backend limpiados"

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES DEL FRONTEND
# ===========================================
print_message "Limpiando archivos temporales del frontend..."

cd /var/www/frontend-csdt

# Limpiar node_modules y reinstalar
rm -rf node_modules
npm install

# Limpiar archivos temporales
find . -name "*.tmp" -delete
find . -name "*.temp" -delete

# Limpiar cache de npm
npm cache clean --force

print_success "✅ Archivos temporales del frontend limpiados"

# ===========================================
# LIMPIAR LOGS DEL SISTEMA
# ===========================================
print_message "Limpiando logs del sistema..."

# Limpiar logs antiguos
find /var/log -name "*.log" -mtime +30 -delete
find /var/log -name "*.gz" -mtime +30 -delete

# Limpiar logs de PM2
pm2 flush

# Limpiar logs de MySQL
find /var/log/mysql -name "*.log" -mtime +30 -delete

print_success "✅ Logs del sistema limpiados"

# ===========================================
# LIMPIAR ARCHIVOS DE BACKUP ANTIGUOS
# ===========================================
print_message "Limpiando archivos de backup antiguos..."

# Limpiar backups antiguos (más de 30 días)
find /var/backups -name "csdt_*" -mtime +30 -delete
find /var/backups -name "*.tar.gz" -mtime +30 -delete

print_success "✅ Archivos de backup antiguos limpiados"

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES DE USUARIOS
# ===========================================
print_message "Limpiando archivos temporales de usuarios..."

# Limpiar archivos temporales de usuarios
find /home -name "*.tmp" -delete
find /home -name "*.temp" -delete
find /home -name "*.log" -mtime +30 -delete

print_success "✅ Archivos temporales de usuarios limpiados"

# ===========================================
# LIMPIAR CACHE DE APLICACIONES
# ===========================================
print_message "Limpiando cache de aplicaciones..."

# Limpiar cache de PHP
rm -rf /var/cache/php/*

# Limpiar cache de Composer
rm -rf /root/.composer/cache/*

# Limpiar cache de NPM
rm -rf /root/.npm/*

print_success "✅ Cache de aplicaciones limpiado"

# ===========================================
# OPTIMIZAR ESPACIO EN DISCO
# ===========================================
print_message "Optimizando espacio en disco..."

# Limpiar archivos duplicados
fdupes -r /var/www/ | head -20

# Limpiar archivos vacíos
find /var/www -type f -empty -delete

print_success "✅ Espacio en disco optimizado"

# ===========================================
# VERIFICAR ESPACIO LIBERADO
# ===========================================
print_message "Verificando espacio liberado..."

echo "Espacio disponible:"
df -h /

echo "Tamaño de directorios principales:"
du -sh /var/www/backend-csdt
du -sh /var/www/frontend-csdt
du -sh /var/log/backend-csdt
du -sh /var/log/frontend-csdt
du -sh /var/backups

print_success "✅ Verificación completada"

print_header "LIMPIEZA COMPLETADA"

print_success "✅ Archivos temporales limpiados correctamente"
print_message "Espacio liberado y sistema optimizado"
print_message "Para verificar el estado, ejecuta: verificar_csdt.sh"
