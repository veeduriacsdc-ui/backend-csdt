#!/bin/bash

# ===========================================
# SCRIPT DE RESTAURACIÓN CSDT
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

print_header "RESTAURACIÓN CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# LISTAR BACKUPS DISPONIBLES
# ===========================================
print_message "Backups disponibles:"
ls -la /var/backups/csdt_backup_*.tar.gz

echo ""
read -p "Ingresa el nombre del backup a restaurar (sin .tar.gz): " BACKUP_NAME

if [ ! -f "/var/backups/${BACKUP_NAME}.tar.gz" ]; then
    print_error "Backup no encontrado: /var/backups/${BACKUP_NAME}.tar.gz"
    exit 1
fi

print_message "Restaurando desde: /var/backups/${BACKUP_NAME}.tar.gz"

# ===========================================
# DETENER SERVICIOS
# ===========================================
print_message "Deteniendo servicios..."
pm2 stop all

# ===========================================
# EXTRAER BACKUP
# ===========================================
print_message "Extrayendo backup..."

cd /var/backups
tar -xzf "${BACKUP_NAME}.tar.gz"

# Obtener el nombre del directorio extraído
EXTRACTED_DIR=$(ls -d csdt_* | head -1)

if [ -z "$EXTRACTED_DIR" ]; then
    print_error "No se pudo extraer el backup"
    exit 1
fi

print_success "✅ Backup extraído: $EXTRACTED_DIR"

# ===========================================
# RESTAURAR BACKEND
# ===========================================
print_message "Restaurando backend..."

# Hacer backup del backend actual
if [ -d "/var/www/backend-csdt" ]; then
    mv /var/www/backend-csdt /var/www/backend-csdt.backup.$(date +%Y%m%d_%H%M%S)
fi

# Extraer backend
tar -xzf "/var/backups/$EXTRACTED_DIR/backend.tar.gz" -C /

# Restaurar configuración
cp "/var/backups/$EXTRACTED_DIR/backend.env" /var/www/backend-csdt/.env
cp "/var/backups/$EXTRACTED_DIR/ecosystem.config.js" /var/www/backend-csdt/

# Restaurar scripts
cp -r "/var/backups/$EXTRACTED_DIR/ayuda_backend" /var/www/backend-csdt/ayuda

print_success "✅ Backend restaurado"

# ===========================================
# RESTAURAR FRONTEND
# ===========================================
print_message "Restaurando frontend..."

# Hacer backup del frontend actual
if [ -d "/var/www/frontend-csdt" ]; then
    mv /var/www/frontend-csdt /var/www/frontend-csdt.backup.$(date +%Y%m%d_%H%M%S)
fi

# Extraer frontend
tar -xzf "/var/backups/$EXTRACTED_DIR/frontend.tar.gz" -C /

# Restaurar configuración
cp "/var/backups/$EXTRACTED_DIR/frontend.env" /var/www/frontend-csdt/.env
cp "/var/backups/$EXTRACTED_DIR/ecosystem-frontend.config.js" /var/www/frontend-csdt/

# Restaurar scripts
cp -r "/var/backups/$EXTRACTED_DIR/ayuda_frontend" /var/www/frontend-csdt/ayuda

print_success "✅ Frontend restaurado"

# ===========================================
# RESTAURAR BASE DE DATOS
# ===========================================
print_message "Restaurando base de datos..."

# Hacer backup de la base de datos actual
mysqldump -u csdt -p123 csdt_final > "/var/backups/csdt_database_backup_$(date +%Y%m%d_%H%M%S).sql"

# Restaurar base de datos
gunzip -c "/var/backups/$EXTRACTED_DIR/database.sql.gz" | mysql -u csdt -p123 csdt_final

print_success "✅ Base de datos restaurada"

# ===========================================
# RESTAURAR LOGS
# ===========================================
print_message "Restaurando logs..."

tar -xzf "/var/backups/$EXTRACTED_DIR/logs.tar.gz" -C /

print_success "✅ Logs restaurados"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Backend
chown -R www-data:www-data /var/www/backend-csdt
chmod -R 755 /var/www/backend-csdt
chmod -R 775 /var/www/backend-csdt/storage
chmod -R 775 /var/www/backend-csdt/bootstrap/cache

# Frontend
chown -R www-data:www-data /var/www/frontend-csdt
chmod -R 755 /var/www/frontend-csdt

# Scripts
chmod +x /var/www/backend-csdt/ayuda/*.sh
chmod +x /var/www/frontend-csdt/ayuda/*.sh

print_success "✅ Permisos configurados"

# ===========================================
# REINSTALAR DEPENDENCIAS
# ===========================================
print_message "Reinstalando dependencias..."

# Backend
cd /var/www/backend-csdt
composer install --optimize-autoloader --no-dev
npm install

# Frontend
cd /var/www/frontend-csdt
npm install
npm run build

print_success "✅ Dependencias reinstaladas"

# ===========================================
# OPTIMIZAR CACHE
# ===========================================
print_message "Optimizando cache..."

cd /var/www/backend-csdt
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "✅ Cache optimizado"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_message "Reiniciando servicios..."

cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production

cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js

pm2 save

print_success "✅ Servicios reiniciados"

# ===========================================
# VERIFICAR RESTAURACIÓN
# ===========================================
print_message "Verificando restauración..."

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

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES
# ===========================================
print_message "Limpiando archivos temporales..."

rm -rf "/var/backups/$EXTRACTED_DIR"

print_success "✅ Archivos temporales eliminados"

print_header "RESTAURACIÓN COMPLETADA"

print_success "✅ Sistema CSDT restaurado exitosamente"
print_warning "Backend: http://64.225.113.49:8000"
print_warning "Frontend: http://64.225.113.49:3000"
print_message "Para verificar el estado, ejecuta: ./verificar_csdt.sh"
