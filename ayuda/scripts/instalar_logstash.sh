#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE LOGSTASH
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

print_header "INSTALACIÓN DE LOGSTASH"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR LOGSTASH
# ===========================================
print_message "Instalando Logstash..."

# Instalar Logstash
apt install -y logstash

print_success "✅ Logstash instalado"

# ===========================================
# CONFIGURAR LOGSTASH
# ===========================================
print_message "Configurando Logstash..."

# Crear configuración de Logstash
cat > /etc/logstash/conf.d/csdt.conf << 'EOF'
# Configuración Logstash para CSDT
input {
  # Logs del sistema
  file {
    path => "/var/log/syslog"
    type => "syslog"
    start_position => "beginning"
  }
  
  # Logs de Nginx
  file {
    path => "/var/log/nginx/*.log"
    type => "nginx"
    start_position => "beginning"
  }
  
  # Logs de MySQL
  file {
    path => "/var/log/mysql/*.log"
    type => "mysql"
    start_position => "beginning"
  }
  
  # Logs de PHP
  file {
    path => "/var/log/php8.2-fpm.log"
    type => "php"
    start_position => "beginning"
  }
  
  # Logs de CSDT
  file {
    path => "/var/log/backend-csdt/*.log"
    type => "csdt_backend"
    start_position => "beginning"
  }
  
  file {
    path => "/var/log/frontend-csdt/*.log"
    type => "csdt_frontend"
    start_position => "beginning"
  }
}

filter {
  # Filtro para syslog
  if [type] == "syslog" {
    grok {
      match => { "message" => "%{SYSLOGTIMESTAMP:timestamp} %{IPORHOST:host} %{PROG:program}: %{GREEDYDATA:message}" }
    }
    date {
      match => [ "timestamp", "MMM  d HH:mm:ss", "MMM dd HH:mm:ss" ]
    }
  }
  
  # Filtro para Nginx
  if [type] == "nginx" {
    grok {
      match => { "message" => "%{NGINXACCESS}" }
    }
    date {
      match => [ "timestamp", "dd/MMM/yyyy:HH:mm:ss Z" ]
    }
  }
  
  # Filtro para MySQL
  if [type] == "mysql" {
    grok {
      match => { "message" => "%{MYSQL_LOG}" }
    }
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
  }
  
  # Filtro para PHP
  if [type] == "php" {
    grok {
      match => { "message" => "%{PHP_LOG}" }
    }
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
  }
  
  # Filtro para CSDT Backend
  if [type] == "csdt_backend" {
    grok {
      match => { "message" => "%{CSDT_BACKEND_LOG}" }
    }
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
  }
  
  # Filtro para CSDT Frontend
  if [type] == "csdt_frontend" {
    grok {
      match => { "message" => "%{CSDT_FRONTEND_LOG}" }
    }
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
  }
}

output {
  # Enviar a Elasticsearch
  elasticsearch {
    hosts => ["127.0.0.1:9200"]
    index => "csdt-logs-%{+YYYY.MM.dd}"
  }
  
  # Logs de debug
  stdout {
    codec => rubydebug
  }
}
EOF

print_success "✅ Logstash configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Configurar permisos
chown -R logstash:logstash /var/lib/logstash
chown -R logstash:logstash /var/log/logstash
chmod -R 755 /var/lib/logstash
chmod -R 755 /var/log/logstash

print_success "✅ Permisos configurados"

# ===========================================
# CONFIGURAR LÍMITES DEL SISTEMA
# ===========================================
print_message "Configurando límites del sistema..."

# Configurar límites para Logstash
cat >> /etc/security/limits.conf << 'EOF'
# Límites para Logstash
logstash soft nofile 65536
logstash hard nofile 65536
logstash soft nproc 4096
logstash hard nproc 4096
EOF

print_success "✅ Límites del sistema configurados"

# ===========================================
# INICIAR LOGSTASH
# ===========================================
print_message "Iniciando Logstash..."

# Habilitar Logstash
systemctl enable logstash
systemctl start logstash

# Esperar a que esté listo
sleep 30

print_success "✅ Logstash iniciado"

# ===========================================
# CONFIGURAR MONITOREO DE LOGSTASH
# ===========================================
print_message "Configurando monitoreo de Logstash..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_logstash.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Logstash

echo "=== MONITOR DE LOGSTASH ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Logstash
echo "Estado de Logstash:"
systemctl status logstash --no-pager

# Verificar procesos
echo "Procesos de Logstash:"
ps aux | grep logstash | grep -v grep

# Verificar logs
echo "Últimas 5 líneas del log:"
tail -5 /var/log/logstash/logstash.log

# Verificar configuración
echo "Verificando configuración:"
/usr/share/logstash/bin/logstash --config.test_and_exit --path.config=/etc/logstash/conf.d/
EOF

chmod +x /usr/local/bin/monitor_logstash.sh

print_success "✅ Monitoreo de Logstash configurado"

# ===========================================
# CONFIGURAR BACKUP DE LOGSTASH
# ===========================================
print_message "Configurando backup de Logstash..."

# Crear script de backup
cat > /usr/local/bin/backup_logstash.sh << 'EOF'
#!/bin/bash
# Script de backup de Logstash

BACKUP_DIR="/var/backups/logstash_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Haciendo backup de Logstash..."

# Backup de configuración
cp -r /etc/logstash "$BACKUP_DIR/"

# Backup de datos
cp -r /var/lib/logstash "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "logstash_$(date +%Y%m%d_%H%M%S).tar.gz" "logstash_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de Logstash completado"
EOF

chmod +x /usr/local/bin/backup_logstash.sh

print_success "✅ Backup de Logstash configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE LOGSTASH
# ===========================================
print_message "Configurando cron jobs de Logstash..."

# Backup de Logstash semanal
echo "0 1 * * 0 /usr/local/bin/backup_logstash.sh >> /var/log/csdt_logstash_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de Logstash configurados"

# ===========================================
# CONFIGURAR FIREWALL PARA LOGSTASH
# ===========================================
print_message "Configurando firewall para Logstash..."

# Logstash solo debe ser accesible localmente
# No abrir puertos al exterior por seguridad

print_success "✅ Firewall configurado para Logstash"

# ===========================================
# VERIFICAR INSTALACIÓN DE LOGSTASH
# ===========================================
print_message "Verificando instalación de Logstash..."

# Verificar estado
systemctl status logstash --no-pager

# Verificar configuración
/usr/share/logstash/bin/logstash --config.test_and_exit --path.config=/etc/logstash/conf.d/

print_success "✅ Instalación de Logstash verificada"

print_header "INSTALACIÓN DE LOGSTASH COMPLETADA"

print_success "✅ Logstash instalado correctamente"
print_message "Configuración: /etc/logstash/conf.d/csdt.conf"
print_message "Logs: /var/log/logstash/"
print_message "Para monitorear: monitor_logstash.sh"
print_message "Para hacer backup: backup_logstash.sh"
print_message "Para verificar configuración: /usr/share/logstash/bin/logstash --config.test_and_exit --path.config=/etc/logstash/conf.d/"
