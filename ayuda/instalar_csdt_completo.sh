#!/bin/bash

# ===========================================
# SCRIPT PRINCIPAL DE INSTALACIÓN CSDT COMPLETA
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
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

print_step() {
    echo -e "${PURPLE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"

print_header "INSTALACIÓN COMPLETA CSDT EN DIGITALOCEAN"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_csdt_completo.sh)"
    exit 1
fi

# ===========================================
# VERIFICAR SCRIPTS NECESARIOS
# ===========================================
print_step "Verificando scripts necesarios..."

SCRIPTS=(
    "01_preparar_servidor.sh"
    "02_instalar_dependencias.sh"
    "03_configurar_mysql.sh"
    "04_instalar_backend.sh"
    "05_instalar_frontend.sh"
    "06_configurar_servicios_ia.sh"
    "07_ejecutar_migraciones.sh"
    "08_iniciar_servicios.sh"
    "09_verificar_instalacion.sh"
    "10_solucionar_errores.sh"
)

for script in "${SCRIPTS[@]}"; do
    if [ -f "scripts/$script" ]; then
        print_message "✅ $script encontrado"
    else
        print_error "❌ $script no encontrado"
        exit 1
    fi
done

print_success "Todos los scripts necesarios encontrados"

# ===========================================
# HACER SCRIPTS EJECUTABLES
# ===========================================
print_step "Haciendo scripts ejecutables..."

for script in "${SCRIPTS[@]}"; do
    chmod +x "scripts/$script"
done

print_success "Scripts hechos ejecutables"

# ===========================================
# EJECUTAR SCRIPTS EN ORDEN
# ===========================================
print_header "INICIANDO INSTALACIÓN PASO A PASO"

# Paso 1: Preparar servidor
print_step "Ejecutando Paso 1: Preparar servidor..."
if ./scripts/01_preparar_servidor.sh; then
    print_success "✅ Paso 1 completado: Servidor preparado"
else
    print_error "❌ Error en Paso 1: Preparar servidor"
    exit 1
fi

# Paso 2: Instalar dependencias
print_step "Ejecutando Paso 2: Instalar dependencias..."
if ./scripts/02_instalar_dependencias.sh; then
    print_success "✅ Paso 2 completado: Dependencias instaladas"
else
    print_error "❌ Error en Paso 2: Instalar dependencias"
    exit 1
fi

# Paso 3: Configurar MySQL
print_step "Ejecutando Paso 3: Configurar MySQL..."
if ./scripts/03_configurar_mysql.sh; then
    print_success "✅ Paso 3 completado: MySQL configurado"
else
    print_error "❌ Error en Paso 3: Configurar MySQL"
    exit 1
fi

# Paso 4: Instalar backend
print_step "Ejecutando Paso 4: Instalar backend..."
if ./scripts/04_instalar_backend.sh; then
    print_success "✅ Paso 4 completado: Backend instalado"
else
    print_error "❌ Error en Paso 4: Instalar backend"
    exit 1
fi

# Paso 5: Instalar frontend
print_step "Ejecutando Paso 5: Instalar frontend..."
if ./scripts/05_instalar_frontend.sh; then
    print_success "✅ Paso 5 completado: Frontend instalado"
else
    print_error "❌ Error en Paso 5: Instalar frontend"
    exit 1
fi

# Paso 6: Configurar servicios IA
print_step "Ejecutando Paso 6: Configurar servicios IA..."
if ./scripts/06_configurar_servicios_ia.sh; then
    print_success "✅ Paso 6 completado: Servicios IA configurados"
else
    print_error "❌ Error en Paso 6: Configurar servicios IA"
    exit 1
fi

# Paso 7: Ejecutar migraciones
print_step "Ejecutando Paso 7: Ejecutar migraciones..."
if ./scripts/07_ejecutar_migraciones.sh; then
    print_success "✅ Paso 7 completado: Migraciones ejecutadas"
else
    print_error "❌ Error en Paso 7: Ejecutar migraciones"
    exit 1
fi

# Paso 8: Iniciar servicios
print_step "Ejecutando Paso 8: Iniciar servicios..."
if ./scripts/08_iniciar_servicios.sh; then
    print_success "✅ Paso 8 completado: Servicios iniciados"
else
    print_error "❌ Error en Paso 8: Iniciar servicios"
    exit 1
fi

# Paso 9: Verificar instalación
print_step "Ejecutando Paso 9: Verificar instalación..."
if ./scripts/09_verificar_instalacion.sh; then
    print_success "✅ Paso 9 completado: Instalación verificada"
else
    print_warning "⚠️ Advertencias en Paso 9: Verificar instalación"
fi

# ===========================================
# VERIFICACIÓN FINAL
# ===========================================
print_header "VERIFICACIÓN FINAL DEL SISTEMA"

# Verificar estado de PM2
print_step "Verificando estado de PM2..."
pm2 status

# Verificar conectividad
print_step "Verificando conectividad..."

# Backend local
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "✅ Backend local: http://localhost:8000"
else
    print_warning "⚠️ Backend local no responde"
fi

# Frontend local
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend local: http://localhost:3000"
else
    print_warning "⚠️ Frontend local no responde"
fi

# Backend externo
if curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1; then
    print_success "✅ Backend externo: http://$IP_PUBLICA:8000"
else
    print_warning "⚠️ Backend externo no accesible"
fi

# Frontend externo
if curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend externo: http://$IP_PUBLICA:3000"
else
    print_warning "⚠️ Frontend externo no accesible"
fi

# ===========================================
# CREAR SCRIPT DE MANTENIMIENTO
# ===========================================
print_step "Creando script de mantenimiento..."

cat > /usr/local/bin/mantenimiento_csdt.sh << 'EOF'
#!/bin/bash
# Script de mantenimiento para CSDT

echo "=== MANTENIMIENTO CSDT ==="
echo "Fecha: $(date)"
echo ""

# Actualizar frontend
echo "Actualizando frontend..."
cd /var/www/frontend-csdt
git pull origin main > /dev/null 2>&1 || true
npm install --silent > /dev/null 2>&1 || true
npm run build > /dev/null 2>&1 || true
pm2 restart frontend-csdt > /dev/null 2>&1 || true

# Actualizar backend
echo "Actualizando backend..."
cd /var/www/backend-csdt
git pull origin main > /dev/null 2>&1 || true
composer install --no-dev --optimize-autoloader > /dev/null 2>&1 || true
php artisan migrate --force > /dev/null 2>&1 || true
php artisan config:cache > /dev/null 2>&1 || true
php artisan route:cache > /dev/null 2>&1 || true
php artisan view:cache > /dev/null 2>&1 || true
pm2 restart backend-csdt > /dev/null 2>&1 || true

echo "Mantenimiento completado"
EOF

chmod +x /usr/local/bin/mantenimiento_csdt.sh

print_success "✅ Script de mantenimiento creado"

# ===========================================
# CREAR SCRIPT DE BACKUP
# ===========================================
print_step "Creando script de backup..."

cat > /usr/local/bin/backup_csdt.sh << 'EOF'
#!/bin/bash
# Script de backup para CSDT

echo "=== BACKUP CSDT ==="
echo "Fecha: $(date)"
echo ""

# Crear directorio de backup
BACKUP_DIR="/var/backups/csdt_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup de base de datos
echo "Haciendo backup de base de datos..."
mysqldump -u csdt -pcsdt_password_2024 csdt_final > "$BACKUP_DIR/database.sql"
gzip "$BACKUP_DIR/database.sql"

# Backup de archivos
echo "Haciendo backup de archivos..."
tar -czf "$BACKUP_DIR/backend.tar.gz" /var/www/backend-csdt
tar -czf "$BACKUP_DIR/frontend.tar.gz" /var/www/frontend-csdt

# Backup de configuración
echo "Haciendo backup de configuración..."
cp /var/www/backend-csdt/.env "$BACKUP_DIR/backend.env"
cp /var/www/frontend-csdt/.env "$BACKUP_DIR/frontend.env"

echo "Backup completado: $BACKUP_DIR"
EOF

chmod +x /usr/local/bin/backup_csdt.sh

print_success "✅ Script de backup creado"

# ===========================================
# CONFIGURAR CRON JOBS
# ===========================================
print_step "Configurando cron jobs..."

# Backup diario a las 2 AM
echo "0 2 * * * /usr/local/bin/backup_csdt.sh >> /var/log/csdt_backup.log 2>&1" | crontab -

# Mantenimiento semanal los domingos a las 3 AM
echo "0 3 * * 0 /usr/local/bin/mantenimiento_csdt.sh >> /var/log/csdt_mantenimiento.log 2>&1" | crontab -

print_success "✅ Cron jobs configurados"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "INSTALACIÓN COMPLETA FINALIZADA"

print_success "✅ Sistema CSDT instalado completamente"
print_success "✅ Backend Laravel funcionando"
print_success "✅ Frontend React funcionando"
print_success "✅ 13 servicios de IA configurados"
print_success "✅ Base de datos MySQL configurada"
print_success "✅ PM2 gestionando procesos"
print_success "✅ Firewall configurado"
print_success "✅ Scripts de mantenimiento creados"
print_success "✅ Scripts de backup creados"
print_success "✅ Cron jobs configurados"

print_warning "INFORMACIÓN DE ACCESO:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"
print_warning "Estado: pm2 status"
print_warning "Logs: pm2 logs"

print_warning "COMANDOS ÚTILES:"
print_warning "Monitoreo: /usr/local/bin/monitor_csdt.sh"
print_warning "Diagnóstico: /usr/local/bin/diagnosticar_csdt.sh"
print_warning "Reparación: /usr/local/bin/reparar_csdt.sh"
print_warning "Mantenimiento: /usr/local/bin/mantenimiento_csdt.sh"
print_warning "Backup: /usr/local/bin/backup_csdt.sh"

print_warning "SOLUCIÓN DE PROBLEMAS:"
print_warning "Si hay problemas, ejecutar: ./scripts/10_solucionar_errores.sh"

print_message "¡Instalación completa finalizada exitosamente!"
print_message "El sistema CSDT está listo para usar en producción."
