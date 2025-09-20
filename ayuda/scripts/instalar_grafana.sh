#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE GRAFANA
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

print_header "INSTALACIÓN DE GRAFANA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR GRAFANA
# ===========================================
print_message "Instalando Grafana..."

# Instalar dependencias
apt update
apt install -y software-properties-common

# Agregar repositorio de Grafana
wget -q -O - https://packages.grafana.com/gpg.key | apt-key add -
echo "deb https://packages.grafana.com/oss/deb stable main" | tee /etc/apt/sources.list.d/grafana.list

# Actualizar paquetes
apt update

# Instalar Grafana
apt install -y grafana

print_success "✅ Grafana instalado"

# ===========================================
# CONFIGURAR GRAFANA
# ===========================================
print_message "Configurando Grafana..."

# Hacer backup de la configuración
cp /etc/grafana/grafana.ini /etc/grafana/grafana.ini.backup

# Configurar Grafana
cat > /etc/grafana/grafana.ini << 'EOF'
# Configuración Grafana para CSDT
[server]
http_port = 3001
domain = 64.225.113.49
root_url = http://64.225.113.49:3001

[database]
type = sqlite3
path = /var/lib/grafana/grafana.db

[security]
admin_user = admin
admin_password = csdt_grafana_2024
secret_key = csdt_secret_key_2024

[log]
mode = file
level = info
EOF

print_success "✅ Grafana configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Configurar permisos
chown -R grafana:grafana /var/lib/grafana
chown -R grafana:grafana /var/log/grafana
chmod -R 755 /var/lib/grafana
chmod -R 755 /var/log/grafana

print_success "✅ Permisos configurados"

# ===========================================
# INICIAR GRAFANA
# ===========================================
print_message "Iniciando Grafana..."

# Habilitar Grafana
systemctl enable grafana-server
systemctl start grafana-server

# Esperar a que esté listo
sleep 30

print_success "✅ Grafana iniciado"

# ===========================================
# CONFIGURAR NGINX PARA GRAFANA
# ===========================================
print_message "Configurando Nginx para Grafana..."

# Crear configuración de Nginx para Grafana
cat > /etc/nginx/sites-available/grafana << 'EOF'
# Configuración Nginx para Grafana
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración de logs
    access_log /var/log/nginx/grafana_access.log;
    error_log /var/log/nginx/grafana_error.log;
    
    # Configuración de proxy para Grafana
    location /grafana/ {
        proxy_pass http://127.0.0.1:3001/;
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
ln -sf /etc/nginx/sites-available/grafana /etc/nginx/sites-enabled/

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx configurado para Grafana"

# ===========================================
# CONFIGURAR MONITOREO DE GRAFANA
# ===========================================
print_message "Configurando monitoreo de Grafana..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_grafana.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Grafana

echo "=== MONITOR DE GRAFANA ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Grafana
echo "Estado de Grafana:"
systemctl status grafana-server --no-pager

# Verificar conexión
echo "Conexión a Grafana:"
curl -s http://127.0.0.1:3001 | head -5

# Verificar logs
echo "Últimas 5 líneas del log:"
tail -5 /var/log/grafana/grafana.log
EOF

chmod +x /usr/local/bin/monitor_grafana.sh

print_success "✅ Monitoreo de Grafana configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA GRAFANA
# ===========================================
print_message "Configurando firewall para Grafana..."

# Permitir puerto de Grafana
ufw allow 3001

print_success "✅ Firewall configurado para Grafana"

# ===========================================
# VERIFICAR INSTALACIÓN DE GRAFANA
# ===========================================
print_message "Verificando instalación de Grafana..."

# Verificar estado
systemctl status grafana-server --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:3001 | grep -q "grafana"; then
    print_success "✅ Grafana funcionando correctamente"
else
    print_warning "⚠️ Grafana no responde"
fi

print_success "✅ Instalación de Grafana verificada"

print_header "INSTALACIÓN DE GRAFANA COMPLETADA"

print_success "✅ Grafana instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 3001"
print_message "URL: http://64.225.113.49:3001"
print_message "Usuario: admin"
print_message "Contraseña: csdt_grafana_2024"
print_message "Para monitorear: monitor_grafana.sh"
