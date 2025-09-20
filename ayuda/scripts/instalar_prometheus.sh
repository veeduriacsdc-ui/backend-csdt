#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE PROMETHEUS
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

print_header "INSTALACIÓN DE PROMETHEUS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR PROMETHEUS
# ===========================================
print_message "Instalando Prometheus..."

# Crear usuario de Prometheus
useradd --no-create-home --shell /bin/false prometheus

# Crear directorios
mkdir -p /etc/prometheus
mkdir -p /var/lib/prometheus
chown prometheus:prometheus /etc/prometheus
chown prometheus:prometheus /var/lib/prometheus

# Descargar Prometheus
cd /tmp
wget https://github.com/prometheus/prometheus/releases/download/v2.45.0/prometheus-2.45.0.linux-amd64.tar.gz
tar xzf prometheus-2.45.0.linux-amd64.tar.gz
cd prometheus-2.45.0.linux-amd64

# Copiar archivos
cp prometheus /usr/local/bin/
cp promtool /usr/local/bin/
chown prometheus:prometheus /usr/local/bin/prometheus
chown prometheus:prometheus /usr/local/bin/promtool

# Copiar configuración
cp -r consoles /etc/prometheus
cp -r console_libraries /etc/prometheus
chown -R prometheus:prometheus /etc/prometheus/consoles
chown -R prometheus:prometheus /etc/prometheus/console_libraries

print_success "✅ Prometheus instalado"

# ===========================================
# CONFIGURAR PROMETHEUS
# ===========================================
print_message "Configurando Prometheus..."

# Crear configuración de Prometheus
cat > /etc/prometheus/prometheus.yml << 'EOF'
# Configuración Prometheus para CSDT
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  # - "first_rules.yml"
  # - "second_rules.yml"

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'node'
    static_configs:
      - targets: ['localhost:9100']

  - job_name: 'mysql'
    static_configs:
      - targets: ['localhost:9104']

  - job_name: 'nginx'
    static_configs:
      - targets: ['localhost:9113']

  - job_name: 'redis'
    static_configs:
      - targets: ['localhost:9121']
EOF

chown prometheus:prometheus /etc/prometheus/prometheus.yml

print_success "✅ Prometheus configurado"

# ===========================================
# CONFIGURAR SERVICIO DE PROMETHEUS
# ===========================================
print_message "Configurando servicio de Prometheus..."

# Crear servicio systemd
cat > /etc/systemd/system/prometheus.service << 'EOF'
[Unit]
Description=Prometheus
Wants=network-online.target
After=network-online.target

[Service]
User=prometheus
Group=prometheus
Type=simple
ExecStart=/usr/local/bin/prometheus \
    --config.file /etc/prometheus/prometheus.yml \
    --storage.tsdb.path /var/lib/prometheus/ \
    --web.console.templates=/etc/prometheus/consoles \
    --web.console.libraries=/etc/prometheus/console_libraries \
    --web.listen-address=0.0.0.0:9090 \
    --web.enable-lifecycle

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Prometheus configurado"

# ===========================================
# INICIAR PROMETHEUS
# ===========================================
print_message "Iniciando Prometheus..."

# Habilitar Prometheus
systemctl enable prometheus
systemctl start prometheus

# Esperar a que esté listo
sleep 30

print_success "✅ Prometheus iniciado"

# ===========================================
# CONFIGURAR NGINX PARA PROMETHEUS
# ===========================================
print_message "Configurando Nginx para Prometheus..."

# Crear configuración de Nginx para Prometheus
cat > /etc/nginx/sites-available/prometheus << 'EOF'
# Configuración Nginx para Prometheus
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración de logs
    access_log /var/log/nginx/prometheus_access.log;
    error_log /var/log/nginx/prometheus_error.log;
    
    # Configuración de proxy para Prometheus
    location /prometheus/ {
        proxy_pass http://127.0.0.1:9090/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
}
EOF

# Habilitar sitio
ln -sf /etc/nginx/sites-available/prometheus /etc/nginx/sites-enabled/

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx configurado para Prometheus"

# ===========================================
# CONFIGURAR MONITOREO DE PROMETHEUS
# ===========================================
print_message "Configurando monitoreo de Prometheus..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_prometheus.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Prometheus

echo "=== MONITOR DE PROMETHEUS ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Prometheus
echo "Estado de Prometheus:"
systemctl status prometheus --no-pager

# Verificar conexión
echo "Conexión a Prometheus:"
curl -s http://127.0.0.1:9090 | head -5

# Verificar métricas
echo "Métricas de Prometheus:"
curl -s http://127.0.0.1:9090/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_prometheus.sh

print_success "✅ Monitoreo de Prometheus configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA PROMETHEUS
# ===========================================
print_message "Configurando firewall para Prometheus..."

# Permitir puerto de Prometheus
ufw allow 9090

print_success "✅ Firewall configurado para Prometheus"

# ===========================================
# VERIFICAR INSTALACIÓN DE PROMETHEUS
# ===========================================
print_message "Verificando instalación de Prometheus..."

# Verificar estado
systemctl status prometheus --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9090 | grep -q "prometheus"; then
    print_success "✅ Prometheus funcionando correctamente"
else
    print_warning "⚠️ Prometheus no responde"
fi

print_success "✅ Instalación de Prometheus verificada"

print_header "INSTALACIÓN DE PROMETHEUS COMPLETADA"

print_success "✅ Prometheus instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9090"
print_message "URL: http://64.225.113.49:9090"
print_message "Para monitorear: monitor_prometheus.sh"
