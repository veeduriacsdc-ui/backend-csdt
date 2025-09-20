#!/bin/bash

# ===========================================
# SCRIPT 1: PREPARAR SERVIDOR
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
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

print_step() {
    echo -e "${GREEN}[PASO]${NC} $1"
}

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"

print_header "PASO 1: PREPARANDO SERVIDOR UBUNTU"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./01_preparar_servidor.sh)"
    exit 1
fi

# ===========================================
# ACTUALIZAR SISTEMA
# ===========================================
print_step "Actualizando paquetes del sistema..."
apt update && apt upgrade -y

print_step "Instalando dependencias básicas..."
apt install -y software-properties-common curl wget unzip git htop nano vim

# ===========================================
# CONFIGURAR ZONA HORARIA
# ===========================================
print_step "Configurando zona horaria..."
timedatectl set-timezone America/Bogota

# ===========================================
# CONFIGURAR SWAP (OPCIONAL)
# ===========================================
print_step "Configurando swap..."
if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    print_message "Swap configurado correctamente"
else
    print_message "Swap ya existe"
fi

# ===========================================
# CONFIGURAR LÍMITES DEL SISTEMA
# ===========================================
print_step "Configurando límites del sistema..."
cat >> /etc/security/limits.conf << 'EOF'
* soft nofile 65536
* hard nofile 65536
* soft nproc 65536
* hard nproc 65536
EOF

# ===========================================
# CONFIGURAR SYSCTL
# ===========================================
print_step "Configurando parámetros del kernel..."
cat >> /etc/sysctl.conf << 'EOF'
# Configuración para aplicaciones web
vm.swappiness = 10
vm.vfs_cache_pressure = 50
net.core.somaxconn = 65535
net.core.netdev_max_backlog = 5000
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_fin_timeout = 10
net.ipv4.tcp_tw_reuse = 1
net.ipv4.tcp_timestamps = 1
net.ipv4.tcp_sack = 1
net.ipv4.tcp_window_scaling = 1
EOF

sysctl -p

# ===========================================
# CREAR DIRECTORIOS NECESARIOS
# ===========================================
print_step "Creando directorios necesarios..."
mkdir -p /var/www
mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
mkdir -p /var/log/mysql
mkdir -p /var/log/nginx

# ===========================================
# CONFIGURAR LOGROTATE
# ===========================================
print_step "Configurando logrotate..."
cat > /etc/logrotate.d/csdt << 'EOF'
/var/log/backend-csdt/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}

/var/log/frontend-csdt/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
EOF

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_step "Verificando configuración del servidor..."

# Verificar memoria
MEMORY=$(free -h | grep "Mem:" | awk '{print $2}')
print_message "Memoria disponible: $MEMORY"

# Verificar espacio en disco
DISK=$(df -h / | awk 'NR==2 {print $4}')
print_message "Espacio disponible: $DISK"

# Verificar CPU
CPU=$(nproc)
print_message "Núcleos de CPU: $CPU"

# Verificar zona horaria
TIMEZONE=$(timedatectl | grep "Time zone" | awk '{print $3}')
print_message "Zona horaria: $TIMEZONE"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "SERVIDOR PREPARADO EXITOSAMENTE"

print_message "✅ Sistema actualizado"
print_message "✅ Dependencias básicas instaladas"
print_message "✅ Zona horaria configurada"
print_message "✅ Swap configurado"
print_message "✅ Límites del sistema configurados"
print_message "✅ Directorios creados"
print_message "✅ Logrotate configurado"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./02_instalar_dependencias.sh"

print_message "¡Servidor preparado correctamente!"
