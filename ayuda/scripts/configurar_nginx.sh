#!/bin/bash

# ===========================================
# SCRIPT DE CONFIGURACIÓN DE NGINX
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

print_header "CONFIGURACIÓN DE NGINX"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR NGINX
# ===========================================
print_message "Instalando Nginx..."

# Instalar Nginx
apt update
apt install -y nginx

# Habilitar Nginx
systemctl enable nginx
systemctl start nginx

print_success "✅ Nginx instalado"

# ===========================================
# CONFIGURAR NGINX PARA CSDT
# ===========================================
print_message "Configurando Nginx para CSDT..."

# Crear configuración principal
cat > /etc/nginx/sites-available/csdt << 'EOF'
# Configuración Nginx para CSDT
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración de logs
    access_log /var/log/nginx/csdt_access.log;
    error_log /var/log/nginx/csdt_error.log;
    
    # Configuración de seguridad
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # Configuración de límites
    client_max_body_size 50M;
    client_body_timeout 60s;
    client_header_timeout 60s;
    
    # Configuración de proxy para backend
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Configuración de proxy para frontend
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Configuración de archivos estáticos
    location /static/ {
        alias /var/www/frontend-csdt/dist/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de archivos de medios
    location /media/ {
        alias /var/www/backend-csdt/storage/app/public/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de favicon
    location /favicon.ico {
        alias /var/www/frontend-csdt/dist/favicon.ico;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de robots.txt
    location /robots.txt {
        alias /var/www/frontend-csdt/dist/robots.txt;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de sitemap.xml
    location /sitemap.xml {
        alias /var/www/frontend-csdt/dist/sitemap.xml;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de archivos de configuración
    location ~ /\. {
        deny all;
    }
    
    # Configuración de archivos de backup
    location ~ \.(bak|backup|old|tmp)$ {
        deny all;
    }
    
    # Configuración de archivos de log
    location ~ \.(log|txt)$ {
        deny all;
    }
}
EOF

# Habilitar sitio
ln -sf /etc/nginx/sites-available/csdt /etc/nginx/sites-enabled/

# Deshabilitar sitio por defecto
rm -f /etc/nginx/sites-enabled/default

print_success "✅ Nginx configurado para CSDT"

# ===========================================
# CONFIGURAR NGINX GLOBAL
# ===========================================
print_message "Configurando Nginx global..."

# Configurar nginx.conf
cat > /etc/nginx/nginx.conf << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;
include /etc/nginx/modules-enabled/*.conf;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    # Configuración básica
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    
    # Configuración de MIME
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # Configuración de logs
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;
    
    # Configuración de compresión
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
    
    # Configuración de límites
    client_max_body_size 50M;
    client_body_timeout 60s;
    client_header_timeout 60s;
    
    # Configuración de buffers
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
    
    # Configuración de timeouts
    send_timeout 60s;
    
    # Configuración de archivos
    open_file_cache max=1000 inactive=20s;
    open_file_cache_valid 30s;
    open_file_cache_min_uses 2;
    open_file_cache_errors on;
    
    # Incluir configuraciones de sitios
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF

print_success "✅ Nginx global configurado"

# ===========================================
# CONFIGURAR SSL (OPCIONAL)
# ===========================================
print_message "Configurando SSL (opcional)..."

# Instalar Certbot
apt install -y certbot python3-certbot-nginx

# Crear configuración SSL
cat > /etc/nginx/sites-available/csdt-ssl << 'EOF'
# Configuración SSL para CSDT
server {
    listen 80;
    server_name 64.225.113.49;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name 64.225.113.49;
    
    # Configuración SSL
    ssl_certificate /etc/letsencrypt/live/64.225.113.49/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/64.225.113.49/privkey.pem;
    
    # Configuración de seguridad SSL
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Configuración de logs
    access_log /var/log/nginx/csdt_ssl_access.log;
    error_log /var/log/nginx/csdt_ssl_error.log;
    
    # Configuración de seguridad
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    
    # Configuración de límites
    client_max_body_size 50M;
    client_body_timeout 60s;
    client_header_timeout 60s;
    
    # Configuración de proxy para backend
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Configuración de proxy para frontend
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Configuración de archivos estáticos
    location /static/ {
        alias /var/www/frontend-csdt/dist/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de archivos de medios
    location /media/ {
        alias /var/www/backend-csdt/storage/app/public/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de favicon
    location /favicon.ico {
        alias /var/www/frontend-csdt/dist/favicon.ico;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de robots.txt
    location /robots.txt {
        alias /var/www/frontend-csdt/dist/robots.txt;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de sitemap.xml
    location /sitemap.xml {
        alias /var/www/frontend-csdt/dist/sitemap.xml;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Configuración de archivos de configuración
    location ~ /\. {
        deny all;
    }
    
    # Configuración de archivos de backup
    location ~ \.(bak|backup|old|tmp)$ {
        deny all;
    }
    
    # Configuración de archivos de log
    location ~ \.(log|txt)$ {
        deny all;
    }
}
EOF

print_success "✅ Configuración SSL creada"

# ===========================================
# CONFIGURAR FIREWALL PARA NGINX
# ===========================================
print_message "Configurando firewall para Nginx..."

# Permitir puertos de Nginx
ufw allow 'Nginx Full'
ufw allow 80
ufw allow 443

print_success "✅ Firewall configurado para Nginx"

# ===========================================
# CONFIGURAR LOGROTATE
# ===========================================
print_message "Configurando logrotate para Nginx..."

# Configurar logrotate
cat > /etc/logrotate.d/nginx << 'EOF'
/var/log/nginx/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 640 nginx adm
    sharedscripts
    postrotate
        if [ -f /var/run/nginx.pid ]; then
            kill -USR1 `cat /var/run/nginx.pid`
        fi
    endscript
}
EOF

print_success "✅ Logrotate configurado"

# ===========================================
# CONFIGURAR MONITOREO DE NGINX
# ===========================================
print_message "Configurando monitoreo de Nginx..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_nginx.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Nginx

echo "=== MONITOR DE NGINX ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Nginx
echo "Estado de Nginx:"
systemctl status nginx --no-pager

# Verificar configuración
echo "Verificando configuración:"
nginx -t

# Verificar puertos
echo "Puertos abiertos:"
netstat -tuln | grep -E ":(80|443)"

# Verificar logs de error
echo "Últimas 5 líneas del log de errores:"
tail -5 /var/log/nginx/error.log

# Verificar logs de acceso
echo "Últimas 5 líneas del log de acceso:"
tail -5 /var/log/nginx/access.log

# Verificar estadísticas
echo "Estadísticas de conexiones:"
ss -tuln | grep -E ":(80|443)"
EOF

chmod +x /usr/local/bin/monitor_nginx.sh

print_success "✅ Monitoreo de Nginx configurado"

# ===========================================
# REINICIAR NGINX
# ===========================================
print_message "Reiniciando Nginx..."

# Verificar configuración
nginx -t

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx reiniciado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_message "Verificando configuración..."

# Verificar estado
systemctl status nginx --no-pager

# Verificar puertos
netstat -tuln | grep -E ":(80|443)"

# Verificar conectividad
if curl -s http://localhost > /dev/null 2>&1; then
    print_success "✅ Nginx funcionando en puerto 80"
else
    print_warning "⚠️ Nginx no responde en puerto 80"
fi

print_success "✅ Configuración verificada"

print_header "CONFIGURACIÓN DE NGINX COMPLETADA"

print_success "✅ Nginx configurado correctamente"
print_message "Puerto 80: http://64.225.113.49"
print_message "Puerto 443: https://64.225.113.49 (requiere SSL)"
print_message "Para monitorear, ejecuta: monitor_nginx.sh"
print_message "Para configurar SSL, ejecuta: certbot --nginx -d 64.225.113.49"
