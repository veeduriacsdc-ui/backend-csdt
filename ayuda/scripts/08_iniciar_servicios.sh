#!/bin/bash

# ===========================================
# SCRIPT 8: INICIAR SERVICIOS
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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
    echo -e "${GREEN}[PASO]${NC} $1"
}

# Variables de configuración
BACKEND_DIR="/var/www/backend-csdt"
FRONTEND_DIR="/var/www/frontend-csdt"
IP_PUBLICA="64.225.113.49"

print_header "PASO 8: INICIANDO SERVICIOS CON PM2"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./08_iniciar_servicios.sh)"
    exit 1
fi

# ===========================================
# VERIFICAR PM2
# ===========================================
print_step "Verificando PM2..."

if command -v pm2 > /dev/null 2>&1; then
    PM2_VERSION=$(pm2 --version)
    print_message "✅ PM2 $PM2_VERSION instalado"
else
    print_error "❌ PM2 no está instalado"
    exit 1
fi

# ===========================================
# CREAR DIRECTORIOS DE LOGS
# ===========================================
print_step "Creando directorios de logs..."

mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
chown -R www-data:www-data /var/log/backend-csdt
chown -R www-data:www-data /var/log/frontend-csdt

print_message "✅ Directorios de logs creados"

# ===========================================
# DETENER SERVICIOS EXISTENTES
# ===========================================
print_step "Deteniendo servicios existentes..."

# Detener todos los procesos PM2
pm2 delete all > /dev/null 2>&1 || true

print_message "✅ Servicios existentes detenidos"

# ===========================================
# INICIAR BACKEND
# ===========================================
print_step "Iniciando backend Laravel..."

cd "$BACKEND_DIR"

# Verificar que existe el archivo ecosystem.config.js
if [ ! -f "ecosystem.config.js" ]; then
    print_error "❌ Archivo ecosystem.config.js no encontrado en $BACKEND_DIR"
    exit 1
fi

# Iniciar backend con PM2
if pm2 start ecosystem.config.js --env production; then
    print_message "✅ Backend iniciado correctamente"
else
    print_error "❌ Error iniciando backend"
    exit 1
fi

# ===========================================
# INICIAR FRONTEND
# ===========================================
print_step "Iniciando frontend React..."

cd "$FRONTEND_DIR"

# Verificar que existe el archivo ecosystem-frontend.config.js
if [ ! -f "ecosystem-frontend.config.js" ]; then
    print_error "❌ Archivo ecosystem-frontend.config.js no encontrado en $FRONTEND_DIR"
    exit 1
fi

# Iniciar frontend con PM2
if pm2 start ecosystem-frontend.config.js; then
    print_message "✅ Frontend iniciado correctamente"
else
    print_error "❌ Error iniciando frontend"
    exit 1
fi

# ===========================================
# CONFIGURAR PM2 PARA INICIO AUTOMÁTICO
# ===========================================
print_step "Configurando PM2 para inicio automático..."

# Configurar PM2 para que inicie automáticamente
pm2 startup > /dev/null 2>&1 || true

# Guardar configuración actual
pm2 save

print_message "✅ PM2 configurado para inicio automático"

# ===========================================
# CONFIGURAR FIREWALL
# ===========================================
print_step "Configurando firewall..."

# Configurar UFW
ufw allow OpenSSH > /dev/null 2>&1 || true
ufw allow 3000 > /dev/null 2>&1 || true
ufw allow 8000 > /dev/null 2>&1 || true
ufw allow 'Nginx Full' > /dev/null 2>&1 || true
ufw --force enable > /dev/null 2>&1 || true

print_message "✅ Firewall configurado"

# ===========================================
# VERIFICAR ESTADO DE SERVICIOS
# ===========================================
print_step "Verificando estado de servicios..."

# Esperar un momento para que los servicios se inicien
sleep 5

# Verificar estado de PM2
print_message "Estado de PM2:"
pm2 status

# ===========================================
# VERIFICAR CONECTIVIDAD LOCAL
# ===========================================
print_step "Verificando conectividad local..."

# Verificar backend local
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_message "✅ Backend local responde correctamente"
else
    print_warning "⚠️ Backend local no responde aún (puede tardar unos segundos)"
fi

# Verificar frontend local
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_message "✅ Frontend local responde correctamente"
else
    print_warning "⚠️ Frontend local no responde aún (puede tardar unos segundos)"
fi

# ===========================================
# VERIFICAR CONECTIVIDAD EXTERNA
# ===========================================
print_step "Verificando conectividad externa..."

# Verificar backend externo
if curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1; then
    print_message "✅ Backend externo accesible"
else
    print_warning "⚠️ Backend externo no accesible aún"
fi

# Verificar frontend externo
if curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1; then
    print_message "✅ Frontend externo accesible"
else
    print_warning "⚠️ Frontend externo no accesible aún"
fi

# ===========================================
# CREAR SCRIPT DE MONITOREO
# ===========================================
print_step "Creando script de monitoreo..."

cat > /usr/local/bin/monitor_csdt.sh << 'EOF'
#!/bin/bash
# Script de monitoreo para CSDT

echo "=== MONITOREO CSDT ==="
echo "Fecha: $(date)"
echo ""

# Estado de PM2
echo "=== ESTADO DE PM2 ==="
pm2 status
echo ""

# Estado de servicios
echo "=== ESTADO DE SERVICIOS ==="
systemctl is-active mysql && echo "✅ MySQL activo" || echo "❌ MySQL inactivo"
systemctl is-active nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active redis-server && echo "✅ Redis activo" || echo "❌ Redis inactivo"
echo ""

# Conectividad
echo "=== CONECTIVIDAD ==="
curl -s http://localhost:8000 > /dev/null && echo "✅ Backend local" || echo "❌ Backend local"
curl -s http://localhost:3000 > /dev/null && echo "✅ Frontend local" || echo "❌ Frontend local"
curl -s http://64.225.113.49:8000 > /dev/null && echo "✅ Backend externo" || echo "❌ Backend externo"
curl -s http://64.225.113.49:3000 > /dev/null && echo "✅ Frontend externo" || echo "❌ Frontend externo"
echo ""

# Uso de recursos
echo "=== RECURSOS DEL SISTEMA ==="
echo "Memoria:"
free -h
echo ""
echo "Disco:"
df -h /
echo ""
echo "CPU:"
top -bn1 | grep "Cpu(s)" | awk '{print $2}' | awk -F'%' '{print $1}'
EOF

chmod +x /usr/local/bin/monitor_csdt.sh

print_message "✅ Script de monitoreo creado"

# ===========================================
# CREAR SCRIPT DE REINICIO
# ===========================================
print_step "Creando script de reinicio..."

cat > /usr/local/bin/restart_csdt.sh << 'EOF'
#!/bin/bash
# Script para reiniciar servicios CSDT

echo "Reiniciando servicios CSDT..."

# Reiniciar PM2
pm2 restart all

# Verificar estado
sleep 5
pm2 status

echo "Servicios reiniciados"
EOF

chmod +x /usr/local/bin/restart_csdt.sh

print_message "✅ Script de reinicio creado"

# ===========================================
# CREAR SCRIPT DE PARADA
# ===========================================
print_step "Creando script de parada..."

cat > /usr/local/bin/stop_csdt.sh << 'EOF'
#!/bin/bash
# Script para detener servicios CSDT

echo "Deteniendo servicios CSDT..."

# Detener PM2
pm2 stop all

echo "Servicios detenidos"
EOF

chmod +x /usr/local/bin/stop_csdt.sh

print_message "✅ Script de parada creado"

# ===========================================
# CONFIGURAR CRON JOB PARA MONITOREO
# ===========================================
print_step "Configurando monitoreo automático..."

# Crear cron job para monitoreo cada 5 minutos
echo "*/5 * * * * /usr/local/bin/monitor_csdt.sh >> /var/log/csdt_monitor.log 2>&1" | crontab -

print_message "✅ Monitoreo automático configurado"

# ===========================================
# VERIFICAR CONFIGURACIÓN FINAL
# ===========================================
print_step "Verificando configuración final..."

# Ejecutar script de monitoreo
/usr/local/bin/monitor_csdt.sh

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "SERVICIOS INICIADOS EXITOSAMENTE"

print_message "✅ Backend Laravel iniciado con PM2"
print_message "✅ Frontend React iniciado con PM2"
print_message "✅ PM2 configurado para inicio automático"
print_message "✅ Firewall configurado"
print_message "✅ Scripts de monitoreo y gestión creados"

print_warning "INFORMACIÓN DE SERVICIOS:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"
print_warning "Estado: pm2 status"
print_warning "Logs: pm2 logs"
print_warning "Monitoreo: /usr/local/bin/monitor_csdt.sh"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./09_verificar_instalacion.sh"

print_message "¡Servicios iniciados correctamente!"
