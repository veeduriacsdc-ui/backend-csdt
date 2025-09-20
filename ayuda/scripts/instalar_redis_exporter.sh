#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE REDIS EXPORTER
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

print_header "INSTALACIÓN DE REDIS EXPORTER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR REDIS EXPORTER
# ===========================================
print_message "Instalando Redis Exporter..."

# Crear usuario de Redis Exporter
useradd --no-create-home --shell /bin/false redis_exporter

# Descargar Redis Exporter
cd /tmp
wget https://github.com/oliver006/redis_exporter/releases/download/v1.55.0/redis_exporter-v1.55.0.linux-amd64.tar.gz
tar xzf redis_exporter-v1.55.0.linux-amd64.tar.gz
cd redis_exporter-v1.55.0.linux-amd64

# Copiar archivo
cp redis_exporter /usr/local/bin/
chown redis_exporter:redis_exporter /usr/local/bin/redis_exporter

print_success "✅ Redis Exporter instalado"

# ===========================================
# CONFIGURAR SERVICIO DE REDIS EXPORTER
# ===========================================
print_message "Configurando servicio de Redis Exporter..."

# Crear servicio systemd
cat > /etc/systemd/system/redis_exporter.service << 'EOF'
[Unit]
Description=Redis Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=redis_exporter
Group=redis_exporter
Type=simple
ExecStart=/usr/local/bin/redis_exporter \
    --redis.addr=redis://127.0.0.1:6379 \
    --redis.password=redis_password_2024 \
    --web.listen-address=0.0.0.0:9121

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Redis Exporter configurado"

# ===========================================
# INICIAR REDIS EXPORTER
# ===========================================
print_message "Iniciando Redis Exporter..."

# Habilitar Redis Exporter
systemctl enable redis_exporter
systemctl start redis_exporter

# Esperar a que esté listo
sleep 10

print_success "✅ Redis Exporter iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE REDIS EXPORTER
# ===========================================
print_message "Configurando monitoreo de Redis Exporter..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_redis_exporter.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Redis Exporter

echo "=== MONITOR DE REDIS EXPORTER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Redis Exporter
echo "Estado de Redis Exporter:"
systemctl status redis_exporter --no-pager

# Verificar conexión
echo "Conexión a Redis Exporter:"
curl -s http://127.0.0.1:9121 | head -5

# Verificar métricas
echo "Métricas de Redis Exporter:"
curl -s http://127.0.0.1:9121/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_redis_exporter.sh

print_success "✅ Monitoreo de Redis Exporter configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA REDIS EXPORTER
# ===========================================
print_message "Configurando firewall para Redis Exporter..."

# Permitir puerto de Redis Exporter
ufw allow 9121

print_success "✅ Firewall configurado para Redis Exporter"

# ===========================================
# VERIFICAR INSTALACIÓN DE REDIS EXPORTER
# ===========================================
print_message "Verificando instalación de Redis Exporter..."

# Verificar estado
systemctl status redis_exporter --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9121 | grep -q "redis_exporter"; then
    print_success "✅ Redis Exporter funcionando correctamente"
else
    print_warning "⚠️ Redis Exporter no responde"
fi

print_success "✅ Instalación de Redis Exporter verificada"

print_header "INSTALACIÓN DE REDIS EXPORTER COMPLETADA"

print_success "✅ Redis Exporter instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9121"
print_message "URL: http://64.225.113.49:9121"
print_message "Para monitorear: monitor_redis_exporter.sh"
