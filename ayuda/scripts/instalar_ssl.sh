#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE CERTIFICADOS SSL
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

print_header "INSTALACIÓN DE CERTIFICADOS SSL"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR CERTBOT
# ===========================================
print_message "Instalando Certbot..."

# Instalar Certbot
apt update
apt install -y certbot python3-certbot-nginx

print_success "✅ Certbot instalado"

# ===========================================
# CONFIGURAR NGINX PARA SSL
# ===========================================
print_message "Configurando Nginx para SSL..."

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

print_success "✅ Nginx configurado para SSL"

# ===========================================
# OBTENER CERTIFICADO SSL
# ===========================================
print_message "Obteniendo certificado SSL..."

# Obtener certificado
certbot --nginx -d 64.225.113.49 --non-interactive --agree-tos --email admin@csdt.com

print_success "✅ Certificado SSL obtenido"

# ===========================================
# CONFIGURAR RENOVACIÓN AUTOMÁTICA
# ===========================================
print_message "Configurando renovación automática..."

# Crear script de renovación
cat > /usr/local/bin/renovar_ssl.sh << 'EOF'
#!/bin/bash
# Script de renovación automática de SSL

echo "Renovando certificados SSL..."
certbot renew --quiet

# Reiniciar Nginx si es necesario
if systemctl is-active --quiet nginx; then
    systemctl reload nginx
    echo "Nginx recargado"
fi

echo "Renovación completada"
EOF

chmod +x /usr/local/bin/renovar_ssl.sh

# Configurar cron para renovación automática
echo "0 12 * * * /usr/local/bin/renovar_ssl.sh >> /var/log/csdt_ssl_renewal.log 2>&1" | crontab -

print_success "✅ Renovación automática configurada"

# ===========================================
# CONFIGURAR FIREWALL PARA SSL
# ===========================================
print_message "Configurando firewall para SSL..."

# Permitir puertos SSL
ufw allow 443
ufw allow 80

print_success "✅ Firewall configurado para SSL"

# ===========================================
# VERIFICAR CERTIFICADO SSL
# ===========================================
print_message "Verificando certificado SSL..."

# Verificar certificado
certbot certificates

# Verificar conectividad SSL
if curl -s https://64.225.113.49 > /dev/null 2>&1; then
    print_success "✅ SSL funcionando correctamente"
else
    print_warning "⚠️ SSL no responde"
fi

print_success "✅ Certificado SSL verificado"

# ===========================================
# CONFIGURAR MONITOREO DE SSL
# ===========================================
print_message "Configurando monitoreo de SSL..."

# Crear script de monitoreo SSL
cat > /usr/local/bin/monitor_ssl.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de SSL

echo "=== MONITOR DE SSL ==="
echo "Fecha: $(date)"
echo ""

# Verificar certificados
echo "Certificados SSL:"
certbot certificates

# Verificar expiración
echo "Días hasta expiración:"
certbot certificates | grep -E "VALID|Expiry Date"

# Verificar conectividad SSL
echo "Conectividad SSL:"
curl -I https://64.225.113.49 2>/dev/null | head -1

# Verificar configuración Nginx
echo "Configuración Nginx:"
nginx -t

# Verificar logs SSL
echo "Últimas 5 líneas del log SSL:"
tail -5 /var/log/nginx/csdt_ssl_error.log
EOF

chmod +x /usr/local/bin/monitor_ssl.sh

print_success "✅ Monitoreo de SSL configurado"

# ===========================================
# CONFIGURAR BACKUP DE CERTIFICADOS
# ===========================================
print_message "Configurando backup de certificados..."

# Crear script de backup de certificados
cat > /usr/local/bin/backup_ssl.sh << 'EOF'
#!/bin/bash
# Script de backup de certificados SSL

BACKUP_DIR="/var/backups/ssl_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup de certificados
cp -r /etc/letsencrypt "$BACKUP_DIR/"

# Backup de configuración Nginx
cp /etc/nginx/sites-available/csdt-ssl "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "ssl_$(date +%Y%m%d_%H%M%S).tar.gz" "ssl_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de certificados SSL completado"
EOF

chmod +x /usr/local/bin/backup_ssl.sh

print_success "✅ Backup de certificados configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE SSL
# ===========================================
print_message "Configurando cron jobs de SSL..."

# Backup de certificados semanal
echo "0 1 * * 0 /usr/local/bin/backup_ssl.sh >> /var/log/csdt_ssl_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de SSL configurados"

# ===========================================
# VERIFICAR CONFIGURACIÓN SSL
# ===========================================
print_message "Verificando configuración SSL..."

# Verificar certificados
certbot certificates

# Verificar configuración Nginx
nginx -t

# Verificar conectividad
if curl -s https://64.225.113.49 > /dev/null 2>&1; then
    print_success "✅ HTTPS funcionando correctamente"
else
    print_warning "⚠️ HTTPS no responde"
fi

print_success "✅ Configuración SSL verificada"

print_header "INSTALACIÓN DE SSL COMPLETADA"

print_success "✅ SSL configurado correctamente"
print_message "HTTP: http://64.225.113.49 (redirige a HTTPS)"
print_message "HTTPS: https://64.225.113.49"
print_message "Para monitorear, ejecuta: monitor_ssl.sh"
print_message "Para renovar, ejecuta: renovar_ssl.sh"
