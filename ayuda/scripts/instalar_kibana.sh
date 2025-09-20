#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE KIBANA
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

print_header "INSTALACIÓN DE KIBANA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR KIBANA
# ===========================================
print_message "Instalando Kibana..."

# Instalar Kibana
apt install -y kibana

print_success "✅ Kibana instalado"

# ===========================================
# CONFIGURAR KIBANA
# ===========================================
print_message "Configurando Kibana..."

# Hacer backup de la configuración
cp /etc/kibana/kibana.yml /etc/kibana/kibana.yml.backup

# Configurar Kibana
cat > /etc/kibana/kibana.yml << 'EOF'
# Configuración Kibana para CSDT
server.port: 5601
server.host: "127.0.0.1"
server.name: "csdt-kibana"
elasticsearch.hosts: ["http://127.0.0.1:9200"]
elasticsearch.username: "elastic"
elasticsearch.password: "elastic"
logging.dest: stdout
logging.level: info
xpack.security.enabled: false
xpack.encryptedSavedObjects.encryptionKey: "csdt_encryption_key_2024"
xpack.reporting.encryptionKey: "csdt_reporting_key_2024"
xpack.security.encryptionKey: "csdt_security_key_2024"
EOF

print_success "✅ Kibana configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Configurar permisos
chown -R kibana:kibana /var/lib/kibana
chown -R kibana:kibana /var/log/kibana
chmod -R 755 /var/lib/kibana
chmod -R 755 /var/log/kibana

print_success "✅ Permisos configurados"

# ===========================================
# INICIAR KIBANA
# ===========================================
print_message "Iniciando Kibana..."

# Habilitar Kibana
systemctl enable kibana
systemctl start kibana

# Esperar a que esté listo
sleep 30

print_success "✅ Kibana iniciado"

# ===========================================
# CONFIGURAR NGINX PARA KIBANA
# ===========================================
print_message "Configurando Nginx para Kibana..."

# Crear configuración de Nginx para Kibana
cat > /etc/nginx/sites-available/kibana << 'EOF'
# Configuración Nginx para Kibana
server {
    listen 80;
    server_name 64.225.113.49;
    
    # Configuración de logs
    access_log /var/log/nginx/kibana_access.log;
    error_log /var/log/nginx/kibana_error.log;
    
    # Configuración de seguridad
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Configuración de proxy para Kibana
    location /kibana/ {
        proxy_pass http://127.0.0.1:5601/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
    }
    
    # Configuración de proxy para Kibana (sin /kibana/)
    location / {
        proxy_pass http://127.0.0.1:5601/;
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
ln -sf /etc/nginx/sites-available/kibana /etc/nginx/sites-enabled/

# Reiniciar Nginx
systemctl restart nginx

print_success "✅ Nginx configurado para Kibana"

# ===========================================
# CONFIGURAR MONITOREO DE KIBANA
# ===========================================
print_message "Configurando monitoreo de Kibana..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_kibana.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Kibana

echo "=== MONITOR DE KIBANA ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Kibana
echo "Estado de Kibana:"
systemctl status kibana --no-pager

# Verificar conexión
echo "Conexión a Kibana:"
curl -s http://127.0.0.1:5601 | head -5

# Verificar información
echo "Información de Kibana:"
curl -s http://127.0.0.1:5601/api/status | head -5

# Verificar logs
echo "Últimas 5 líneas del log:"
tail -5 /var/log/kibana/kibana.log
EOF

chmod +x /usr/local/bin/monitor_kibana.sh

print_success "✅ Monitoreo de Kibana configurado"

# ===========================================
# CONFIGURAR BACKUP DE KIBANA
# ===========================================
print_message "Configurando backup de Kibana..."

# Crear script de backup
cat > /usr/local/bin/backup_kibana.sh << 'EOF'
#!/bin/bash
# Script de backup de Kibana

BACKUP_DIR="/var/backups/kibana_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Haciendo backup de Kibana..."

# Backup de configuración
cp /etc/kibana/kibana.yml "$BACKUP_DIR/"

# Backup de datos
cp -r /var/lib/kibana "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "kibana_$(date +%Y%m%d_%H%M%S).tar.gz" "kibana_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de Kibana completado"
EOF

chmod +x /usr/local/bin/backup_kibana.sh

print_success "✅ Backup de Kibana configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE KIBANA
# ===========================================
print_message "Configurando cron jobs de Kibana..."

# Backup de Kibana semanal
echo "0 1 * * 0 /usr/local/bin/backup_kibana.sh >> /var/log/csdt_kibana_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de Kibana configurados"

# ===========================================
# CONFIGURAR FIREWALL PARA KIBANA
# ===========================================
print_message "Configurando firewall para Kibana..."

# Permitir puerto de Kibana
ufw allow 5601

print_success "✅ Firewall configurado para Kibana"

# ===========================================
# VERIFICAR INSTALACIÓN DE KIBANA
# ===========================================
print_message "Verificando instalación de Kibana..."

# Verificar estado
systemctl status kibana --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:5601 | grep -q "kibana"; then
    print_success "✅ Kibana funcionando correctamente"
else
    print_warning "⚠️ Kibana no responde"
fi

# Verificar información
curl -s http://127.0.0.1:5601 | head -10

print_success "✅ Instalación de Kibana verificada"

print_header "INSTALACIÓN DE KIBANA COMPLETADA"

print_success "✅ Kibana instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 5601"
print_message "URL: http://64.225.113.49:5601"
print_message "Para monitorear: monitor_kibana.sh"
print_message "Para hacer backup: backup_kibana.sh"
print_message "Para conectar: curl http://127.0.0.1:5601"
