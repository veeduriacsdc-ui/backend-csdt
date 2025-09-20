#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE NGINX EXPORTER
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

print_header "INSTALACIÓN DE NGINX EXPORTER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR NGINX EXPORTER
# ===========================================
print_message "Instalando Nginx Exporter..."

# Crear usuario de Nginx Exporter
useradd --no-create-home --shell /bin/false nginx_exporter

# Descargar Nginx Exporter
cd /tmp
wget https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v0.11.0/nginx-prometheus-exporter_0.11.0_linux_amd64.tar.gz
tar xzf nginx-prometheus-exporter_0.11.0_linux_amd64.tar.gz
cd nginx-prometheus-exporter_0.11.0_linux_amd64

# Copiar archivo
cp nginx-prometheus-exporter /usr/local/bin/
chown nginx_exporter:nginx_exporter /usr/local/bin/nginx-prometheus-exporter

print_success "✅ Nginx Exporter instalado"

# ===========================================
# CONFIGURAR NGINX EXPORTER
# ===========================================
print_message "Configurando Nginx Exporter..."

# Crear archivo de configuración
cat > /etc/nginx_exporter.conf << 'EOF'
# Configuración Nginx Exporter para CSDT
server {
    listen 80;
    server_name 64.225.113.49;
    
    location /nginx_status {
        stub_status on;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
}
EOF

# Aplicar configuración a Nginx
cat >> /etc/nginx/sites-available/csdt << 'EOF'

    # Configuración de status para Nginx Exporter
    location /nginx_status {
        stub_status on;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
EOF

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx Exporter configurado"

# ===========================================
# CONFIGURAR SERVICIO DE NGINX EXPORTER
# ===========================================
print_message "Configurando servicio de Nginx Exporter..."

# Crear servicio systemd
cat > /etc/systemd/system/nginx_exporter.service << 'EOF'
[Unit]
Description=Nginx Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=nginx_exporter
Group=nginx_exporter
Type=simple
ExecStart=/usr/local/bin/nginx-prometheus-exporter \
    --nginx.scrape-uri=http://127.0.0.1/nginx_status \
    --web.listen-address=0.0.0.0:9113

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Nginx Exporter configurado"

# ===========================================
# INICIAR NGINX EXPORTER
# ===========================================
print_message "Iniciando Nginx Exporter..."

# Habilitar Nginx Exporter
systemctl enable nginx_exporter
systemctl start nginx_exporter

# Esperar a que esté listo
sleep 10

print_success "✅ Nginx Exporter iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE NGINX EXPORTER
# ===========================================
print_message "Configurando monitoreo de Nginx Exporter..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_nginx_exporter.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Nginx Exporter

echo "=== MONITOR DE NGINX EXPORTER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Nginx Exporter
echo "Estado de Nginx Exporter:"
systemctl status nginx_exporter --no-pager

# Verificar conexión
echo "Conexión a Nginx Exporter:"
curl -s http://127.0.0.1:9113 | head -5

# Verificar métricas
echo "Métricas de Nginx Exporter:"
curl -s http://127.0.0.1:9113/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_nginx_exporter.sh

print_success "✅ Monitoreo de Nginx Exporter configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA NGINX EXPORTER
# ===========================================
print_message "Configurando firewall para Nginx Exporter..."

# Permitir puerto de Nginx Exporter
ufw allow 9113

print_success "✅ Firewall configurado para Nginx Exporter"

# ===========================================
# VERIFICAR INSTALACIÓN DE NGINX EXPORTER
# ===========================================
print_message "Verificando instalación de Nginx Exporter..."

# Verificar estado
systemctl status nginx_exporter --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9113 | grep -q "nginx_exporter"; then
    print_success "✅ Nginx Exporter funcionando correctamente"
else
    print_warning "⚠️ Nginx Exporter no responde"
fi

print_success "✅ Instalación de Nginx Exporter verificada"

print_header "INSTALACIÓN DE NGINX EXPORTER COMPLETADA"

print_success "✅ Nginx Exporter instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9113"
print_message "URL: http://64.225.113.49:9113"
print_message "Para monitorear: monitor_nginx_exporter.sh"
