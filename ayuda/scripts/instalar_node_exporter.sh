#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE NODE EXPORTER
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

print_header "INSTALACIÓN DE NODE EXPORTER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR NODE EXPORTER
# ===========================================
print_message "Instalando Node Exporter..."

# Crear usuario de Node Exporter
useradd --no-create-home --shell /bin/false node_exporter

# Descargar Node Exporter
cd /tmp
wget https://github.com/prometheus/node_exporter/releases/download/v1.6.1/node_exporter-1.6.1.linux-amd64.tar.gz
tar xzf node_exporter-1.6.1.linux-amd64.tar.gz
cd node_exporter-1.6.1.linux-amd64

# Copiar archivo
cp node_exporter /usr/local/bin/
chown node_exporter:node_exporter /usr/local/bin/node_exporter

print_success "✅ Node Exporter instalado"

# ===========================================
# CONFIGURAR SERVICIO DE NODE EXPORTER
# ===========================================
print_message "Configurando servicio de Node Exporter..."

# Crear servicio systemd
cat > /etc/systemd/system/node_exporter.service << 'EOF'
[Unit]
Description=Node Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter \
    --web.listen-address=0.0.0.0:9100 \
    --collector.systemd \
    --collector.processes \
    --collector.cpu \
    --collector.memory \
    --collector.disk \
    --collector.network \
    --collector.filesystem \
    --collector.loadavg \
    --collector.time \
    --collector.uname \
    --collector.vmstat \
    --collector.conntrack \
    --collector.entropy \
    --collector.interrupts \
    --collector.ksmd \
    --collector.logind \
    --collector.meminfo \
    --collector.mountstats \
    --collector.netdev \
    --collector.nfs \
    --collector.nfsd \
    --collector.powersupply \
    --collector.pressure \
    --collector.rapl \
    --collector.runit \
    --collector.supervisord \
    --collector.systemd \
    --collector.tcpstat \
    --collector.thermal_zone \
    --collector.timex \
    --collector.udp_queues \
    --collector.uname \
    --collector.vmstat \
    --collector.wifi \
    --collector.xfs \
    --collector.zfs

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Node Exporter configurado"

# ===========================================
# INICIAR NODE EXPORTER
# ===========================================
print_message "Iniciando Node Exporter..."

# Habilitar Node Exporter
systemctl enable node_exporter
systemctl start node_exporter

# Esperar a que esté listo
sleep 10

print_success "✅ Node Exporter iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE NODE EXPORTER
# ===========================================
print_message "Configurando monitoreo de Node Exporter..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_node_exporter.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Node Exporter

echo "=== MONITOR DE NODE EXPORTER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Node Exporter
echo "Estado de Node Exporter:"
systemctl status node_exporter --no-pager

# Verificar conexión
echo "Conexión a Node Exporter:"
curl -s http://127.0.0.1:9100 | head -5

# Verificar métricas
echo "Métricas de Node Exporter:"
curl -s http://127.0.0.1:9100/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_node_exporter.sh

print_success "✅ Monitoreo de Node Exporter configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA NODE EXPORTER
# ===========================================
print_message "Configurando firewall para Node Exporter..."

# Permitir puerto de Node Exporter
ufw allow 9100

print_success "✅ Firewall configurado para Node Exporter"

# ===========================================
# VERIFICAR INSTALACIÓN DE NODE EXPORTER
# ===========================================
print_message "Verificando instalación de Node Exporter..."

# Verificar estado
systemctl status node_exporter --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9100 | grep -q "node_exporter"; then
    print_success "✅ Node Exporter funcionando correctamente"
else
    print_warning "⚠️ Node Exporter no responde"
fi

print_success "✅ Instalación de Node Exporter verificada"

print_header "INSTALACIÓN DE NODE EXPORTER COMPLETADA"

print_success "✅ Node Exporter instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9100"
print_message "URL: http://64.225.113.49:9100"
print_message "Para monitorear: monitor_node_exporter.sh"
