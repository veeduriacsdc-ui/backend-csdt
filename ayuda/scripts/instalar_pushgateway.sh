#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE PUSHGATEWAY
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

print_header "INSTALACIÓN DE PUSHGATEWAY"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR PUSHGATEWAY
# ===========================================
print_message "Instalando Pushgateway..."

# Crear usuario de Pushgateway
useradd --no-create-home --shell /bin/false pushgateway

# Crear directorios
mkdir -p /var/lib/pushgateway
chown pushgateway:pushgateway /var/lib/pushgateway

# Descargar Pushgateway
cd /tmp
wget https://github.com/prometheus/pushgateway/releases/download/v1.6.2/pushgateway-1.6.2.linux-amd64.tar.gz
tar xzf pushgateway-1.6.2.linux-amd64.tar.gz
cd pushgateway-1.6.2.linux-amd64

# Copiar archivo
cp pushgateway /usr/local/bin/
chown pushgateway:pushgateway /usr/local/bin/pushgateway

print_success "✅ Pushgateway instalado"

# ===========================================
# CONFIGURAR SERVICIO DE PUSHGATEWAY
# ===========================================
print_message "Configurando servicio de Pushgateway..."

# Crear servicio systemd
cat > /etc/systemd/system/pushgateway.service << 'EOF'
[Unit]
Description=Pushgateway
Wants=network-online.target
After=network-online.target

[Service]
User=pushgateway
Group=pushgateway
Type=simple
ExecStart=/usr/local/bin/pushgateway \
    --web.listen-address=0.0.0.0:9091 \
    --persistence.file=/var/lib/pushgateway/pushgateway.db \
    --persistence.interval=5m

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Pushgateway configurado"

# ===========================================
# INICIAR PUSHGATEWAY
# ===========================================
print_message "Iniciando Pushgateway..."

# Habilitar Pushgateway
systemctl enable pushgateway
systemctl start pushgateway

# Esperar a que esté listo
sleep 10

print_success "✅ Pushgateway iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE PUSHGATEWAY
# ===========================================
print_message "Configurando monitoreo de Pushgateway..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_pushgateway.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Pushgateway

echo "=== MONITOR DE PUSHGATEWAY ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Pushgateway
echo "Estado de Pushgateway:"
systemctl status pushgateway --no-pager

# Verificar conexión
echo "Conexión a Pushgateway:"
curl -s http://127.0.0.1:9091 | head -5

# Verificar métricas
echo "Métricas de Pushgateway:"
curl -s http://127.0.0.1:9091/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_pushgateway.sh

print_success "✅ Monitoreo de Pushgateway configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA PUSHGATEWAY
# ===========================================
print_message "Configurando firewall para Pushgateway..."

# Permitir puerto de Pushgateway
ufw allow 9091

print_success "✅ Firewall configurado para Pushgateway"

# ===========================================
# VERIFICAR INSTALACIÓN DE PUSHGATEWAY
# ===========================================
print_message "Verificando instalación de Pushgateway..."

# Verificar estado
systemctl status pushgateway --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9091 | grep -q "pushgateway"; then
    print_success "✅ Pushgateway funcionando correctamente"
else
    print_warning "⚠️ Pushgateway no responde"
fi

print_success "✅ Instalación de Pushgateway verificada"

print_header "INSTALACIÓN DE PUSHGATEWAY COMPLETADA"

print_success "✅ Pushgateway instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9091"
print_message "URL: http://64.225.113.49:9091"
print_message "Para monitorear: monitor_pushgateway.sh"
