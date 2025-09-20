#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE LIBRERÍAS
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

print_header "INSTALACIÓN DE LIBRERÍAS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR LIBRERÍAS DEL SISTEMA
# ===========================================
print_message "Instalando librerías del sistema..."

# Librerías básicas
apt update
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Librerías de desarrollo
apt install -y build-essential libssl-dev libffi-dev python3-dev python3-pip

# Librerías de base de datos
apt install -y libmysqlclient-dev

# Librerías de imagen
apt install -y libjpeg-dev libpng-dev libwebp-dev

# Librerías de compresión
apt install -y zlib1g-dev libbz2-dev

print_success "✅ Librerías del sistema instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE PHP
# ===========================================
print_message "Instalando librerías de PHP..."

# Instalar extensiones de PHP
apt install -y php8.2-curl php8.2-gd php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath php8.2-mysql php8.2-sqlite3 php8.2-intl php8.2-xmlrpc php8.2-soap

# Instalar librerías adicionales
apt install -y php8.2-redis php8.2-memcached php8.2-imagick

print_success "✅ Librerías de PHP instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE NODE.JS
# ===========================================
print_message "Instalando librerías de Node.js..."

# Instalar librerías globales de Node.js
npm install -g pm2
npm install -g nodemon
npm install -g typescript
npm install -g @vitejs/plugin-react

print_success "✅ Librerías de Node.js instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DEL BACKEND
# ===========================================
print_message "Instalando librerías del backend..."

cd /var/www/backend-csdt

# Instalar dependencias PHP
composer install --optimize-autoloader --no-dev

# Instalar dependencias Node.js
npm install

print_success "✅ Librerías del backend instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DEL FRONTEND
# ===========================================
print_message "Instalando librerías del frontend..."

cd /var/www/frontend-csdt

# Instalar dependencias
npm install

# Instalar librerías adicionales para IA
npm install axios
npm install leaflet
npm install react-leaflet
npm install chart.js
npm install react-chartjs-2

print_success "✅ Librerías del frontend instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE PYTHON (OPCIONAL)
# ===========================================
print_message "Instalando librerías de Python..."

# Instalar librerías de Python para IA
pip3 install openai
pip3 install anthropic
pip3 install requests
pip3 install numpy
pip3 install pandas
pip3 install scikit-learn

print_success "✅ Librerías de Python instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE REDIS (OPCIONAL)
# ===========================================
print_message "Instalando Redis..."

# Instalar Redis
apt install -y redis-server

# Configurar Redis
systemctl enable redis-server
systemctl start redis-server

print_success "✅ Redis instalado"

# ===========================================
# INSTALAR LIBRERÍAS DE MONITOREO
# ===========================================
print_message "Instalando librerías de monitoreo..."

# Instalar htop, iotop, nethogs
apt install -y htop iotop nethogs

# Instalar librerías de monitoreo de red
apt install -y netstat-nat tcpdump

print_success "✅ Librerías de monitoreo instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE SEGURIDAD
# ===========================================
print_message "Instalando librerías de seguridad..."

# Instalar fail2ban
apt install -y fail2ban

# Configurar fail2ban
cat > /etc/fail2ban/jail.local << 'EOF'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[sshd]
enabled = true
port = ssh
logpath = /var/log/auth.log
maxretry = 3
EOF

systemctl enable fail2ban
systemctl start fail2ban

print_success "✅ Librerías de seguridad instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE BACKUP
# ===========================================
print_message "Instalando librerías de backup..."

# Instalar rsync
apt install -y rsync

# Instalar librerías de compresión
apt install -y zip unzip p7zip-full

print_success "✅ Librerías de backup instaladas"

# ===========================================
# INSTALAR LIBRERÍAS DE DESARROLLO
# ===========================================
print_message "Instalando librerías de desarrollo..."

# Instalar Git
apt install -y git

# Instalar herramientas de desarrollo
apt install -y vim nano emacs

# Instalar herramientas de red
apt install -y net-tools iputils-ping traceroute

print_success "✅ Librerías de desarrollo instaladas"

# ===========================================
# VERIFICAR INSTALACIÓN
# ===========================================
print_message "Verificando instalación..."

# Verificar PHP
echo "Versión de PHP:"
php --version

# Verificar Composer
echo "Versión de Composer:"
composer --version

# Verificar Node.js
echo "Versión de Node.js:"
node --version

# Verificar NPM
echo "Versión de NPM:"
npm --version

# Verificar PM2
echo "Versión de PM2:"
pm2 --version

# Verificar Redis
echo "Estado de Redis:"
systemctl status redis-server --no-pager

# Verificar fail2ban
echo "Estado de fail2ban:"
systemctl status fail2ban --no-pager

print_success "✅ Verificación completada"

# ===========================================
# OPTIMIZAR CONFIGURACIÓN
# ===========================================
print_message "Optimizando configuración..."

# Optimizar PHP
cat >> /etc/php/8.2/fpm/php.ini << 'EOF'
; Configuración optimizada para CSDT
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 3000
EOF

# Reiniciar PHP-FPM
systemctl restart php8.2-fpm

print_success "✅ Configuración optimizada"

print_header "INSTALACIÓN DE LIBRERÍAS COMPLETADA"

print_success "✅ Todas las librerías instaladas correctamente"
print_message "El sistema está listo para funcionar con todas las dependencias"
print_message "Para verificar el estado, ejecuta: ./verificar_csdt.sh"
