#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE CHRONOGRAF
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

print_header() {
    echo -e "${BLUE}===========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===========================================${NC}"
}

print_header "INSTALACIÓN DE CHRONOGRAF"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR CHRONOGRAF
# ===========================================
print_message "Instalando Chronograf..."

# Agregar clave GPG
wget -qO- https://repos.influxdata.com/influxdb.key | apt-key add -

# Agregar repositorio
echo "deb https://repos.influxdata.com/ubuntu focal stable" | tee /etc/apt/sources.list.d/chronograf.list

# Instalar
apt update
apt install -y chronograf

print_message "✅ Chronograf instalado"

# ===========================================
# CONFIGURAR Y INICIAR CHRONOGRAF
# ===========================================
print_message "Configurando Chronograf..."

# Habilitar e iniciar
systemctl enable chronograf
systemctl start chronograf

# Configurar firewall
ufw allow 8888

print_message "✅ Chronograf configurado"

# ===========================================
# VERIFICAR INSTALACIÓN
# ===========================================
print_message "Verificando instalación..."

if curl -s http://127.0.0.1:8888 > /dev/null 2>&1; then
    print_message "✅ Chronograf funcionando en http://64.225.113.49:8888"
else
    echo -e "${YELLOW}[WARNING]${NC} Chronograf no responde"
fi

print_header "INSTALACIÓN DE CHRONOGRAF COMPLETADA"

print_message "✅ Chronograf instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 8888"
print_message "URL: http://64.225.113.49:8888"
