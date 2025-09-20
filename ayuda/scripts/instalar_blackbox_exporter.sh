#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE BLACKBOX EXPORTER
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

print_header "INSTALACIÓN DE BLACKBOX EXPORTER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR BLACKBOX EXPORTER
# ===========================================
print_message "Instalando Blackbox Exporter..."

# Crear usuario de Blackbox Exporter
useradd --no-create-home --shell /bin/false blackbox_exporter

# Crear directorios
mkdir -p /etc/blackbox_exporter
chown blackbox_exporter:blackbox_exporter /etc/blackbox_exporter

# Descargar Blackbox Exporter
cd /tmp
wget https://github.com/prometheus/blackbox_exporter/releases/download/v0.24.0/blackbox_exporter-0.24.0.linux-amd64.tar.gz
tar xzf blackbox_exporter-0.24.0.linux-amd64.tar.gz
cd blackbox_exporter-0.24.0.linux-amd64

# Copiar archivos
cp blackbox_exporter /usr/local/bin/
chown blackbox_exporter:blackbox_exporter /usr/local/bin/blackbox_exporter

print_success "✅ Blackbox Exporter instalado"

# ===========================================
# CONFIGURAR BLACKBOX EXPORTER
# ===========================================
print_message "Configurando Blackbox Exporter..."

# Crear configuración de Blackbox Exporter
cat > /etc/blackbox_exporter/blackbox.yml << 'EOF'
# Configuración Blackbox Exporter para CSDT
modules:
  http_2xx:
    prober: http
    timeout: 5s
    http:
      valid_http_versions: ["HTTP/1.1", "HTTP/2.0"]
      valid_status_codes: [200, 201, 202, 203, 204, 205, 206, 207, 208, 226]
      method: GET
      headers:
        User-Agent: "Blackbox Exporter"
      no_follow_redirects: false
      fail_if_ssl: false
      fail_if_not_ssl: false
      tls_config:
        insecure_skip_verify: false
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true

  http_post_2xx:
    prober: http
    timeout: 5s
    http:
      valid_http_versions: ["HTTP/1.1", "HTTP/2.0"]
      valid_status_codes: [200, 201, 202, 203, 204, 205, 206, 207, 208, 226]
      method: POST
      headers:
        Content-Type: application/json
        User-Agent: "Blackbox Exporter"
      body: '{}'
      no_follow_redirects: false
      fail_if_ssl: false
      fail_if_not_ssl: false
      tls_config:
        insecure_skip_verify: false
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true

  tcp_connect:
    prober: tcp
    timeout: 5s
    tcp:
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true

  icmp:
    prober: icmp
    timeout: 5s
    icmp:
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true

  dns_tcp:
    prober: dns
    timeout: 5s
    dns:
      query_name: "example.com"
      query_type: "A"
      valid_rcodes:
        - NOERROR
      validate_answer_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      validate_authority_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      validate_additional_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true

  dns_udp:
    prober: dns
    timeout: 5s
    dns:
      query_name: "example.com"
      query_type: "A"
      valid_rcodes:
        - NOERROR
      validate_answer_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      validate_authority_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      validate_additional_rrs:
        fail_if_matches_regexp:
          - ".*\\.mycompany\\.com$"
        fail_if_not_matches_regexp:
          - ".*\\.mycompany\\.com$"
      preferred_ip_protocol: "ip4"
      ip_protocol_fallback: true
EOF

chown blackbox_exporter:blackbox_exporter /etc/blackbox_exporter/blackbox.yml

print_success "✅ Blackbox Exporter configurado"

# ===========================================
# CONFIGURAR SERVICIO DE BLACKBOX EXPORTER
# ===========================================
print_message "Configurando servicio de Blackbox Exporter..."

# Crear servicio systemd
cat > /etc/systemd/system/blackbox_exporter.service << 'EOF'
[Unit]
Description=Blackbox Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=blackbox_exporter
Group=blackbox_exporter
Type=simple
ExecStart=/usr/local/bin/blackbox_exporter \
    --config.file=/etc/blackbox_exporter/blackbox.yml \
    --web.listen-address=0.0.0.0:9115

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de Blackbox Exporter configurado"

# ===========================================
# INICIAR BLACKBOX EXPORTER
# ===========================================
print_message "Iniciando Blackbox Exporter..."

# Habilitar Blackbox Exporter
systemctl enable blackbox_exporter
systemctl start blackbox_exporter

# Esperar a que esté listo
sleep 10

print_success "✅ Blackbox Exporter iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE BLACKBOX EXPORTER
# ===========================================
print_message "Configurando monitoreo de Blackbox Exporter..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_blackbox_exporter.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Blackbox Exporter

echo "=== MONITOR DE BLACKBOX EXPORTER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Blackbox Exporter
echo "Estado de Blackbox Exporter:"
systemctl status blackbox_exporter --no-pager

# Verificar conexión
echo "Conexión a Blackbox Exporter:"
curl -s http://127.0.0.1:9115 | head -5

# Verificar métricas
echo "Métricas de Blackbox Exporter:"
curl -s http://127.0.0.1:9115/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_blackbox_exporter.sh

print_success "✅ Monitoreo de Blackbox Exporter configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA BLACKBOX EXPORTER
# ===========================================
print_message "Configurando firewall para Blackbox Exporter..."

# Permitir puerto de Blackbox Exporter
ufw allow 9115

print_success "✅ Firewall configurado para Blackbox Exporter"

# ===========================================
# VERIFICAR INSTALACIÓN DE BLACKBOX EXPORTER
# ===========================================
print_message "Verificando instalación de Blackbox Exporter..."

# Verificar estado
systemctl status blackbox_exporter --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9115 | grep -q "blackbox_exporter"; then
    print_success "✅ Blackbox Exporter funcionando correctamente"
else
    print_warning "⚠️ Blackbox Exporter no responde"
fi

print_success "✅ Instalación de Blackbox Exporter verificada"

print_header "INSTALACIÓN DE BLACKBOX EXPORTER COMPLETADA"

print_success "✅ Blackbox Exporter instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9115"
print_message "URL: http://64.225.113.49:9115"
print_message "Para monitorear: monitor_blackbox_exporter.sh"
