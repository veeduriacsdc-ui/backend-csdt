#!/bin/bash

# ===========================================
# SCRIPT DE BACKUP CSDT
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

print_header "BACKUP CSDT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# CREAR DIRECTORIO DE BACKUP
# ===========================================
BACKUP_DIR="/var/backups/csdt_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

print_message "Creando backup en: $BACKUP_DIR"

# ===========================================
# BACKUP DE BASE DE DATOS
# ===========================================
print_message "Haciendo backup de base de datos..."

mysqldump -u csdt -p123 csdt_final > "$BACKUP_DIR/database.sql"
gzip "$BACKUP_DIR/database.sql"

print_success "✅ Base de datos respaldada"

# ===========================================
# BACKUP DE ARCHIVOS
# ===========================================
print_message "Haciendo backup de archivos..."

# Backup del backend
tar -czf "$BACKUP_DIR/backend.tar.gz" /var/www/backend-csdt

# Backup del frontend
tar -czf "$BACKUP_DIR/frontend.tar.gz" /var/www/frontend-csdt

print_success "✅ Archivos respaldados"

# ===========================================
# BACKUP DE CONFIGURACIÓN
# ===========================================
print_message "Haciendo backup de configuración..."

# Backup de archivos .env
cp /var/www/backend-csdt/.env "$BACKUP_DIR/backend.env"
cp /var/www/frontend-csdt/.env "$BACKUP_DIR/frontend.env"

# Backup de archivos ecosystem
cp /var/www/backend-csdt/ecosystem.config.js "$BACKUP_DIR/"
cp /var/www/frontend-csdt/ecosystem-frontend.config.js "$BACKUP_DIR/"

# Backup de scripts
cp -r /var/www/backend-csdt/ayuda "$BACKUP_DIR/ayuda_backend"
cp -r /var/www/frontend-csdt/ayuda "$BACKUP_DIR/ayuda_frontend"

print_success "✅ Configuración respaldada"

# ===========================================
# BACKUP DE LOGS
# ===========================================
print_message "Haciendo backup de logs..."

tar -czf "$BACKUP_DIR/logs.tar.gz" /var/log/backend-csdt /var/log/frontend-csdt

print_success "✅ Logs respaldados"

# ===========================================
# CREAR ARCHIVO DE INFORMACIÓN
# ===========================================
print_message "Creando archivo de información..."

cat > "$BACKUP_DIR/info.txt" << EOF
BACKUP CSDT - $(date)
===========================================

Información del servidor:
- IP Pública: 64.225.113.49
- IP Privada: 10.120.0.2
- Fecha: $(date)
- Usuario: $(whoami)

Archivos incluidos:
- database.sql.gz: Base de datos MySQL
- backend.tar.gz: Código fuente del backend
- frontend.tar.gz: Código fuente del frontend
- backend.env: Variables de entorno del backend
- frontend.env: Variables de entorno del frontend
- ecosystem.config.js: Configuración PM2 del backend
- ecosystem-frontend.config.js: Configuración PM2 del frontend
- ayuda_backend/: Scripts de gestión del backend
- ayuda_frontend/: Scripts de gestión del frontend
- logs.tar.gz: Logs del sistema

Para restaurar:
1. Extraer archivos: tar -xzf backend.tar.gz -C /
2. Restaurar base de datos: gunzip -c database.sql.gz | mysql -u csdt -p123 csdt_final
3. Restaurar configuración: cp *.env /var/www/*/ayuda/ /var/www/*/
4. Reiniciar servicios: pm2 restart all

EOF

print_success "✅ Archivo de información creado"

# ===========================================
# COMPRIMIR BACKUP COMPLETO
# ===========================================
print_message "Comprimiendo backup completo..."

cd /var/backups
tar -czf "csdt_backup_$(date +%Y%m%d_%H%M%S).tar.gz" "csdt_$(date +%Y%m%d_%H%M%S)"

print_success "✅ Backup comprimido"

# ===========================================
# LIMPIAR BACKUPS ANTIGUOS
# ===========================================
print_message "Limpiando backups antiguos..."

# Mantener solo los últimos 7 backups
find /var/backups -name "csdt_backup_*.tar.gz" -mtime +7 -delete

print_success "✅ Backups antiguos eliminados"

# ===========================================
# VERIFICAR BACKUP
# ===========================================
print_message "Verificando backup..."

if [ -f "/var/backups/csdt_backup_$(date +%Y%m%d_%H%M%S).tar.gz" ]; then
    print_success "✅ Backup creado correctamente"
    echo "Ubicación: /var/backups/csdt_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    echo "Tamaño: $(du -h /var/backups/csdt_backup_$(date +%Y%m%d_%H%M%S).tar.gz | cut -f1)"
else
    print_error "❌ Error al crear backup"
fi

print_header "BACKUP COMPLETADO"

print_success "✅ Backup CSDT completado exitosamente"
print_message "Ubicación: /var/backups/"
print_message "Para restaurar, usa el script de restauración"
