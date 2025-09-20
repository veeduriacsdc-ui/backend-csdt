#!/bin/bash

# ===========================================
# SCRIPT 2: INSTALAR DEPENDENCIAS
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

print_header "PASO 2: INSTALANDO DEPENDENCIAS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./02_instalar_dependencias.sh)"
    exit 1
fi

# ===========================================
# INSTALAR PHP 8.2
# ===========================================
print_step "Instalando PHP 8.2 y extensiones..."

# Agregar repositorio de PHP
add-apt-repository ppa:ondrej/php -y
apt update

# Instalar PHP 8.2 y extensiones necesarias
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-sqlite3 php8.2-intl php8.2-redis php8.2-imagick

# Configurar PHP
print_step "Configurando PHP..."
cat > /etc/php/8.2/fpm/conf.d/99-csdt.ini << 'EOF'
; Configuración para CSDT
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M
max_file_uploads = 20
date.timezone = America/Bogota
session.gc_maxlifetime = 7200
session.cookie_lifetime = 7200
EOF

# Reiniciar PHP-FPM
systemctl restart php8.2-fpm

print_message "✅ PHP 8.2 instalado y configurado"

# ===========================================
# INSTALAR COMPOSER
# ===========================================
print_step "Instalando Composer..."

# Descargar e instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Configurar Composer
print_step "Configurando Composer..."
composer config --global process-timeout 2000
composer config --global memory-limit -1

print_message "✅ Composer instalado y configurado"

# ===========================================
# INSTALAR NODE.JS 18.x
# ===========================================
print_step "Instalando Node.js 18.x..."

# Agregar repositorio de Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -

# Instalar Node.js y npm
apt-get install -y nodejs

# Instalar PM2 globalmente
npm install -g pm2

# Configurar npm
print_step "Configurando npm..."
npm config set registry https://registry.npmjs.org/
npm config set cache /root/.npm-cache

print_message "✅ Node.js 18.x y PM2 instalados"

# ===========================================
# INSTALAR NGINX
# ===========================================
print_step "Instalando Nginx..."

apt install -y nginx

# Configurar Nginx
print_step "Configurando Nginx..."
cat > /etc/nginx/sites-available/csdt << 'EOF'
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración para el backend
    location /api {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Configuración para el frontend
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF

# Habilitar sitio
ln -sf /etc/nginx/sites-available/csdt /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Configurar límites de Nginx
cat >> /etc/nginx/nginx.conf << 'EOF'
# Configuración adicional para CSDT
worker_processes auto;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    client_max_body_size 100M;
    client_body_timeout 60s;
    client_header_timeout 60s;
    keepalive_timeout 65s;
    send_timeout 60s;
}
EOF

# Probar configuración de Nginx
nginx -t

# Reiniciar Nginx
systemctl restart nginx
systemctl enable nginx

print_message "✅ Nginx instalado y configurado"

# ===========================================
# INSTALAR MYSQL 8.0
# ===========================================
print_step "Instalando MySQL 8.0..."

apt install -y mysql-server-8.0

# Configurar MySQL
print_step "Configurando MySQL..."
cat > /etc/mysql/mysql.conf.d/csdt.cnf << 'EOF'
[mysqld]
# Configuración para CSDT
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
tmp_table_size = 64M
max_heap_table_size = 64M
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
EOF

# Reiniciar MySQL
systemctl restart mysql
systemctl enable mysql

print_message "✅ MySQL 8.0 instalado y configurado"

# ===========================================
# INSTALAR HERRAMIENTAS ADICIONALES
# ===========================================
print_step "Instalando herramientas adicionales..."

apt install -y redis-server supervisor htop iotop nethogs fail2ban ufw

# Configurar Redis
print_step "Configurando Redis..."
systemctl enable redis-server
systemctl start redis-server

# Configurar Fail2ban
print_step "Configurando Fail2ban..."
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

print_message "✅ Herramientas adicionales instaladas"

# ===========================================
# VERIFICAR INSTALACIONES
# ===========================================
print_step "Verificando instalaciones..."

# Verificar PHP
PHP_VERSION=$(php --version | head -n1 | cut -d' ' -f2)
print_message "✅ PHP $PHP_VERSION instalado"

# Verificar Composer
COMPOSER_VERSION=$(composer --version | cut -d' ' -f3)
print_message "✅ Composer $COMPOSER_VERSION instalado"

# Verificar Node.js
NODE_VERSION=$(node --version | cut -d'v' -f2)
print_message "✅ Node.js $NODE_VERSION instalado"

# Verificar npm
NPM_VERSION=$(npm --version)
print_message "✅ npm $NPM_VERSION instalado"

# Verificar PM2
PM2_VERSION=$(pm2 --version)
print_message "✅ PM2 $PM2_VERSION instalado"

# Verificar MySQL
MYSQL_VERSION=$(mysql --version | cut -d' ' -f3 | cut -d',' -f1)
print_message "✅ MySQL $MYSQL_VERSION instalado"

# Verificar Nginx
NGINX_VERSION=$(nginx -v 2>&1 | cut -d'/' -f2)
print_message "✅ Nginx $NGINX_VERSION instalado"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "DEPENDENCIAS INSTALADAS EXITOSAMENTE"

print_message "✅ PHP 8.2 con todas las extensiones"
print_message "✅ Composer configurado"
print_message "✅ Node.js 18.x y npm"
print_message "✅ PM2 para gestión de procesos"
print_message "✅ Nginx configurado"
print_message "✅ MySQL 8.0 configurado"
print_message "✅ Redis instalado"
print_message "✅ Herramientas adicionales"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./03_configurar_mysql.sh"

print_message "¡Dependencias instaladas correctamente!"
