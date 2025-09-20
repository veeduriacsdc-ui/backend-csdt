#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE MYSQL EXPORTER
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

print_header "INSTALACIÓN DE MYSQL EXPORTER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR MYSQL EXPORTER
# ===========================================
print_message "Instalando MySQL Exporter..."

# Crear usuario de MySQL Exporter
useradd --no-create-home --shell /bin/false mysql_exporter

# Descargar MySQL Exporter
cd /tmp
wget https://github.com/prometheus/mysqld_exporter/releases/download/v0.15.0/mysqld_exporter-0.15.0.linux-amd64.tar.gz
tar xzf mysqld_exporter-0.15.0.linux-amd64.tar.gz
cd mysqld_exporter-0.15.0.linux-amd64

# Copiar archivo
cp mysqld_exporter /usr/local/bin/
chown mysql_exporter:mysql_exporter /usr/local/bin/mysqld_exporter

print_success "✅ MySQL Exporter instalado"

# ===========================================
# CONFIGURAR MYSQL EXPORTER
# ===========================================
print_message "Configurando MySQL Exporter..."

# Crear archivo de configuración
cat > /etc/mysql_exporter.cnf << 'EOF'
[client]
user=csdt
password=123
host=127.0.0.1
port=3306
EOF

chown mysql_exporter:mysql_exporter /etc/mysql_exporter.cnf
chmod 600 /etc/mysql_exporter.cnf

print_success "✅ MySQL Exporter configurado"

# ===========================================
# CONFIGURAR SERVICIO DE MYSQL EXPORTER
# ===========================================
print_message "Configurando servicio de MySQL Exporter..."

# Crear servicio systemd
cat > /etc/systemd/system/mysql_exporter.service << 'EOF'
[Unit]
Description=MySQL Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=mysql_exporter
Group=mysql_exporter
Type=simple
ExecStart=/usr/local/bin/mysqld_exporter \
    --config.my-cnf=/etc/mysql_exporter.cnf \
    --web.listen-address=0.0.0.0:9104

[Install]
WantedBy=multi-user.target
EOF

# Recargar systemd
systemctl daemon-reload

print_success "✅ Servicio de MySQL Exporter configurado"

# ===========================================
# INICIAR MYSQL EXPORTER
# ===========================================
print_message "Iniciando MySQL Exporter..."

# Habilitar MySQL Exporter
systemctl enable mysql_exporter
systemctl start mysql_exporter

# Esperar a que esté listo
sleep 10

print_success "✅ MySQL Exporter iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE MYSQL EXPORTER
# ===========================================
print_message "Configurando monitoreo de MySQL Exporter..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_mysql_exporter.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de MySQL Exporter

echo "=== MONITOR DE MYSQL EXPORTER ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de MySQL Exporter
echo "Estado de MySQL Exporter:"
systemctl status mysql_exporter --no-pager

# Verificar conexión
echo "Conexión a MySQL Exporter:"
curl -s http://127.0.0.1:9104 | head -5

# Verificar métricas
echo "Métricas de MySQL Exporter:"
curl -s http://127.0.0.1:9104/metrics | head -10
EOF

chmod +x /usr/local/bin/monitor_mysql_exporter.sh

print_success "✅ Monitoreo de MySQL Exporter configurado"

# ===========================================
# CONFIGURAR FIREWALL PARA MYSQL EXPORTER
# ===========================================
print_message "Configurando firewall para MySQL Exporter..."

# Permitir puerto de MySQL Exporter
ufw allow 9104

print_success "✅ Firewall configurado para MySQL Exporter"

# ===========================================
# VERIFICAR INSTALACIÓN DE MYSQL EXPORTER
# ===========================================
print_message "Verificando instalación de MySQL Exporter..."

# Verificar estado
systemctl status mysql_exporter --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9104 | grep -q "mysql_exporter"; then
    print_success "✅ MySQL Exporter funcionando correctamente"
else
    print_warning "⚠️ MySQL Exporter no responde"
fi

print_success "✅ Instalación de MySQL Exporter verificada"

print_header "INSTALACIÓN DE MYSQL EXPORTER COMPLETADA"

print_success "✅ MySQL Exporter instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9104"
print_message "URL: http://64.225.113.49:9104"
print_message "Para monitorear: monitor_mysql_exporter.sh"
