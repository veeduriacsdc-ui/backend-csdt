#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE CADVISOR
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

print_header "INSTALACIÓN DE CADVISOR"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR CADVISOR
# ===========================================
print_message "Instalando cAdvisor..."

# Crear usuario de cAdvisor
useradd --no-create-home --shell /bin/false cadvisor

# Descargar cAdvisor
cd /tmp
wget https://github.com/google/cadvisor/releases/download/v0.47.0/cadvisor-v0.47.0-linux-amd64.tar.gz
tar xzf cadvisor-v0.47.0-linux-amd64.tar.gz
cd cadvisor-v0.47.0-linux-amd64

# Copiar archivo
cp cadvisor /usr/local/bin/
chown cadvisor:cadvisor /usr/local/bin/cadvisor

print_success "✅ cAdvisor instalado"

# ===========================================
# CONFIGURAR SERVICIO DE CADVISOR
# ===========================================
print_message "Configurando servicio de cAdvisor..."

# Crear servicio systemd
cat > /etc/systemd/system/cadvisor.service << 'EOF'
[Unit]
Description=cAdvisor
Wants=network-online.target
After=network-online.target

[Service]
User=cadvisor
Group=cadvisor
Type=simple
ExecStart=/usr/local/bin/cadvisor \
    --web.listen-address=0.0.0.0:8080 \
    --housekeeping_interval=30s \
    --max_housekeeping_interval=35s \
    --event_storage_event_limit=default=0 \
    --event_storage_age_limit=default=0 \
    --docker_only \
    --disable_metrics=percpu,sched,tcp,udp,disk,diskIO,accelerator,hugetlb,referenced_memory,cpu_topology,resctrl

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de cAdvisor configurado"

# ===========================================
# INICIAR CADVISOR
# ===========================================
print_message "Iniciando cAdvisor..."

# Habilitar cAdvisor
systemctl enable cadvisor
systemctl start cadvisor

# Esperar a que esté listo
sleep 10

print_success "✅ cAdvisor iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE CADVISOR
# ===========================================
print_message "Configurando monitoreo de cAdvisor..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_cadvisor.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de cAdvisor

echo "=== MONITOR DE CADVISOR ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de cAdvisor
echo "Estado de cAdvisor:"
systemctl status cadvisor --no-pager

# Verificar conexión
echo "Conexión a cAdvisor:"
curl -s http://127.0.0.1:8080 | head -5

# Verificar métricas
echo "Métricas de cAdvisor:"
curl -s http://127.0.0.1:8080/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_cadvisor.sh

print_success "✅ Monitoreo de cAdvisor configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA CADVISOR
# ===========================================
print_message "Configurando firewall para cAdvisor..."

# Permitir puerto de cAdvisor
ufw allow 8080

print_success "✅ Firewall configurado para cAdvisor"

# ===========================================
# VERIFICAR INSTALACIÓN DE CADVISOR
# ===========================================
print_message "Verificando instalación de cAdvisor..."

# Verificar estado
systemctl status cadvisor --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:8080 | grep -q "cadvisor"; then
    print_success "✅ cAdvisor funcionando correctamente"
else
    print_warning "⚠️ cAdvisor no responde"
fi

print_success "✅ Instalación de cAdvisor verificada"

print_header "INSTALACIÓN DE CADVISOR COMPLETADA"

print_success "✅ cAdvisor instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 8080"
print_message "URL: http://64.225.113.49:8080"
print_message "Para monitorear: monitor_cadvisor.sh"
