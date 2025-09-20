#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE ALERTMANAGER
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

print_header "INSTALACIÓN DE ALERTMANAGER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR ALERTMANAGER
# ===========================================
print_message "Instalando Alertmanager..."

# Crear usuario de Alertmanager
useradd --no-create-home --shell /bin/false alertmanager

# Crear directorios
mkdir -p /etc/alertmanager
mkdir -p /var/lib/alertmanager
chown alertmanager:alertmanager /etc/alertmanager
chown alertmanager:alertmanager /var/lib/alertmanager

# Descargar Alertmanager
cd /tmp
wget https://github.com/prometheus/alertmanager/releases/download/v0.26.0/alertmanager-0.26.0.linux-amd64.tar.gz
tar xzf alertmanager-0.26.0.linux-amd64.tar.gz
cd alertmanager-0.26.0.linux-amd64

# Copiar archivos
cp alertmanager /usr/local/bin/
cp amtool /usr/local/bin/
chown alertmanager:alertmanager /usr/local/bin/alertmanager
chown alertmanager:alertmanager /usr/local/bin/amtool

print_success "✅ Alertmanager instalado"

# ===========================================
# CONFIGURAR ALERTMANAGER
# ===========================================
print_message "Configurando Alertmanager..."

# Crear configuración de Alertmanager
cat > /etc/alertmanager/alertmanager.yml << 'EOF'
# Configuración Alertmanager para CSDT
global:
  smtp_smarthost: 'localhost:587'
  smtp_from: 'alerts@csdt.com'

route:
  group_by: ['alertname']
  group_wait: 10s
  group_interval: 10s
  repeat_interval: 1h
  receiver: 'web.hook'

receivers:
- name: 'web.hook'
  webhook_configs:
  - url: 'http://127.0.0.1:5001/'

inhibit_rules:
  - source_match:
      severity: 'critical'
    target_match:
      severity: 'warning'
    equal: ['alertname', 'dev', 'instance']
EOF

chown alertmanager:alertmanager /etc/alertmanager/alertmanager.yml

print_success "✅ Alertmanager configurado"

# ===========================================
# CONFIGURAR SERVICIO DE ALERTMANAGER
# ===========================================
print_message "Configurando servicio de Alertmanager..."

# Crear servicio systemd
cat > /etc/systemd/system/alertmanager.service << 'EOF'
[Unit]
Description=Alertmanager
Wants=network-online.target
After=network-online.target

[Service]
User=alertmanager
Group=alertmanager
Type=simple
ExecStart=/usr/local/bin/alertmanager \
    --config.file=/etc/alertmanager/alertmanager.yml \
    --storage.path=/var/lib/alertmanager \
    --web.listen-address=0.0.0.0:9093

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Alertmanager configurado"

# ===========================================
# INICIAR ALERTMANAGER
# ===========================================
print_message "Iniciando Alertmanager..."

# Habilitar Alertmanager
systemctl enable alertmanager
systemctl start alertmanager

# Esperar a que esté listo
sleep 30

print_success "✅ Alertmanager iniciado"

# ===========================================
# CONFIGURAR NGINX PARA ALERTMANAGER
# ===========================================
print_message "Configurando Nginx para Alertmanager..."

# Crear configuración de Nginx para Alertmanager
cat > /etc/nginx/sites-available/alertmanager << 'EOF'
# Configuración Nginx para Alertmanager
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración de logs
    access_log /var/log/nginx/alertmanager_access.log;
    error_log /var/log/nginx/alertmanager_error.log;
    
    # Configuración de proxy para Alertmanager
    location /alertmanager/ {
        proxy_pass http://127.0.0.1:9093/;
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
ln -sf /etc/nginx/sites-available/alertmanager /etc/nginx/sites-enabled/

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx configurado para Alertmanager"

# ===========================================
# CONFIGURAR MONITOREO DE ALERTMANAGER
# ===========================================
print_message "Configurando monitoreo de Alertmanager..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_alertmanager.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Alertmanager

echo "=== MONITOR DE ALERTMANAGER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Alertmanager
echo "Estado de Alertmanager:"
systemctl status alertmanager --no-pager

# Verificar conexión
echo "Conexión a Alertmanager:"
curl -s http://127.0.0.1:9093 | head -5

# Verificar alertas
echo "Alertas activas:"
curl -s http://127.0.0.1:9093/api/v1/alerts | head -10
EOF

chmod +x /usr/local/bin/monitor_alertmanager.sh

print_success "✅ Monitoreo de Alertmanager configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA ALERTMANAGER
# ===========================================
print_message "Configurando firewall para Alertmanager..."

# Permitir puerto de Alertmanager
ufw allow 9093

print_success "✅ Firewall configurado para Alertmanager"

# ===========================================
# VERIFICAR INSTALACIÓN DE ALERTMANAGER
# ===========================================
print_message "Verificando instalación de Alertmanager..."

# Verificar estado
systemctl status alertmanager --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9093 | grep -q "alertmanager"; then
    print_success "✅ Alertmanager funcionando correctamente"
else
    print_warning "⚠️ Alertmanager no responde"
fi

print_success "✅ Instalación de Alertmanager verificada"

print_header "INSTALACIÓN DE ALERTMANAGER COMPLETADA"

print_success "✅ Alertmanager instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9093"
print_message "URL: http://64.225.113.49:9093"
print_message "Para monitorear: monitor_alertmanager.sh"
