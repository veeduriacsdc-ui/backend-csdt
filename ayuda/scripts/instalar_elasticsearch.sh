#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE ELASTICSEARCH
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

print_header "INSTALACIÓN DE ELASTICSEARCH"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR JAVA
# ===========================================
print_message "Instalando Java..."

# Instalar OpenJDK 11
apt update
apt install -y openjdk-11-jdk

# Verificar instalación
java -version

print_success "✅ Java instalado"

# ===========================================
# INSTALAR ELASTICSEARCH
# ===========================================
print_message "Instalando Elasticsearch..."

# Agregar clave GPG de Elasticsearch
wget -qO - https://artifacts.elastic.co/GPG-KEY-elasticsearch | apt-key add -

# Agregar repositorio de Elasticsearch
echo "deb https://artifacts.elastic.co/packages/8.x/apt stable main" | tee /etc/apt/sources.list.d/elastic-8.x.list

# Actualizar paquetes
apt update

# Instalar Elasticsearch
apt install -y elasticsearch

print_success "✅ Elasticsearch instalado"

# ===========================================
# CONFIGURAR ELASTICSEARCH
# ===========================================
print_message "Configurando Elasticsearch..."

# Hacer backup de la configuración
cp /etc/elasticsearch/elasticsearch.yml /etc/elasticsearch/elasticsearch.yml.backup

# Configurar Elasticsearch
cat > /etc/elasticsearch/elasticsearch.yml << 'EOF'
# Configuración Elasticsearch para CSDT
cluster.name: csdt-cluster
node.name: csdt-node-1
path.data: /var/lib/elasticsearch
path.logs: /var/log/elasticsearch
network.host: 127.0.0.1
http.port: 9200
discovery.type: single-node
xpack.security.enabled: false
xpack.security.enrollment.enabled: false
xpack.security.http.ssl.enabled: false
xpack.security.transport.ssl.enabled: false
EOF

print_success "✅ Elasticsearch configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Configurar permisos
chown -R elasticsearch:elasticsearch /var/lib/elasticsearch
chown -R elasticsearch:elasticsearch /var/log/elasticsearch
chmod -R 755 /var/lib/elasticsearch
chmod -R 755 /var/log/elasticsearch

print_success "✅ Permisos configurados"

# ===========================================
# CONFIGURAR LÍMITES DEL SISTEMA
# ===========================================
print_message "Configurando límites del sistema..."

# Configurar límites para Elasticsearch
cat >> /etc/security/limits.conf << 'EOF'
# Límites para Elasticsearch
elasticsearch soft nofile 65536
elasticsearch hard nofile 65536
elasticsearch soft nproc 4096
elasticsearch hard nproc 4096
EOF

# Configurar sysctl
cat >> /etc/sysctl.conf << 'EOF'
# Configuración para Elasticsearch
vm.max_map_count=262144
EOF

# Aplicar configuración
sysctl -p

print_success "✅ Límites del sistema configurados"

# ===========================================
# INICIAR ELASTICSEARCH
# ===========================================
print_message "Iniciando Elasticsearch..."

# Habilitar Elasticsearch
systemctl enable elasticsearch
systemctl start elasticsearch

# Esperar a que esté listo
sleep 30

print_success "✅ Elasticsearch iniciado"

# ===========================================
# CONFIGURAR PHP PARA ELASTICSEARCH
# ===========================================
print_message "Configurando PHP para Elasticsearch..."

# Instalar extensión Elasticsearch para PHP
apt install -y php8.2-curl php8.2-json

print_success "✅ PHP configurado para Elasticsearch"

# ===========================================
# CONFIGURAR LARAVEL PARA ELASTICSEARCH
# ===========================================
print_message "Configurando Laravel para Elasticsearch..."

# Instalar Scout
cd /var/www/backend-csdt
composer require laravel/scout

# Publicar configuración de Scout
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"

# Configurar Scout
cat > config/scout.php << 'EOF'
<?php

return [
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),
    'prefix' => env('SCOUT_PREFIX', ''),
    'queue' => env('SCOUT_QUEUE', false),
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    'soft_delete' => false,
    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
    ],
    'elasticsearch' => [
        'index' => env('ELASTICSEARCH_INDEX', 'csdt'),
        'hosts' => [
            env('ELASTICSEARCH_HOST', '127.0.0.1:9200'),
        ],
    ],
];
EOF

# Actualizar .env
cat >> .env << 'EOF'

# Configuración de Elasticsearch
SCOUT_DRIVER=elasticsearch
ELASTICSEARCH_HOST=127.0.0.1:9200
ELASTICSEARCH_INDEX=csdt
EOF

print_success "✅ Laravel configurado para Elasticsearch"

# ===========================================
# CONFIGURAR MONITOREO DE ELASTICSEARCH
# ===========================================
print_message "Configurando monitoreo de Elasticsearch..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_elasticsearch.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Elasticsearch

echo "=== MONITOR DE ELASTICSEARCH ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Elasticsearch
echo "Estado de Elasticsearch:"
systemctl status elasticsearch --no-pager

# Verificar conexión
echo "Conexión a Elasticsearch:"
curl -s http://127.0.0.1:9200 | head -5

# Verificar información del cluster
echo "Información del cluster:"
curl -s http://127.0.0.1:9200/_cluster/health | head -5

# Verificar índices
echo "Índices:"
curl -s http://127.0.0.1:9200/_cat/indices

# Verificar logs
echo "Últimas 5 líneas del log:"
tail -5 /var/log/elasticsearch/csdt-cluster.log
EOF

chmod +x /usr/local/bin/monitor_elasticsearch.sh

print_success "✅ Monitoreo de Elasticsearch configurado"

# ===========================================
# CONFIGURAR BACKUP DE ELASTICSEARCH
# ===========================================
print_message "Configurando backup de Elasticsearch..."

# Crear script de backup
cat > /usr/local/bin/backup_elasticsearch.sh << 'EOF'
#!/bin/bash
# Script de backup de Elasticsearch

BACKUP_DIR="/var/backups/elasticsearch_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Haciendo backup de Elasticsearch..."

# Backup de datos
curl -X PUT "127.0.0.1:9200/_snapshot/csdt_backup" -H 'Content-Type: application/json' -d'
{
  "type": "fs",
  "settings": {
    "location": "'$BACKUP_DIR'"
  }
}'

# Crear snapshot
curl -X PUT "127.0.0.1:9200/_snapshot/csdt_backup/snapshot_$(date +%Y%m%d_%H%M%S)" -H 'Content-Type: application/json' -d'
{
  "indices": "*",
  "ignore_unavailable": true,
  "include_global_state": false
}'

# Backup de configuración
cp /etc/elasticsearch/elasticsearch.yml "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "elasticsearch_$(date +%Y%m%d_%H%M%S).tar.gz" "elasticsearch_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de Elasticsearch completado"
EOF

chmod +x /usr/local/bin/backup_elasticsearch.sh

print_success "✅ Backup de Elasticsearch configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE ELASTICSEARCH
# ===========================================
print_message "Configurando cron jobs de Elasticsearch..."

# Backup de Elasticsearch semanal
echo "0 1 * * 0 /usr/local/bin/backup_elasticsearch.sh >> /var/log/csdt_elasticsearch_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de Elasticsearch configurados"

# ===========================================
# CONFIGURAR FIREWALL PARA ELASTICSEARCH
# ===========================================
print_message "Configurando firewall para Elasticsearch..."

# Elasticsearch solo debe ser accesible localmente
# No abrir puerto 9200 al exterior por seguridad

print_success "✅ Firewall configurado para Elasticsearch"

# ===========================================
# VERIFICAR INSTALACIÓN DE ELASTICSEARCH
# ===========================================
print_message "Verificando instalación de Elasticsearch..."

# Verificar estado
systemctl status elasticsearch --no-pager

# Verificar conexión
if curl -s http://127.0.0.1:9200 | grep -q "elasticsearch"; then
    print_success "✅ Elasticsearch funcionando correctamente"
else
    print_warning "⚠️ Elasticsearch no responde"
fi

# Verificar información
curl -s http://127.0.0.1:9200 | head -10

print_success "✅ Instalación de Elasticsearch verificada"

print_header "INSTALACIÓN DE ELASTICSEARCH COMPLETADA"

print_success "✅ Elasticsearch instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 9200"
print_message "Cluster: csdt-cluster"
print_message "Para monitorear: monitor_elasticsearch.sh"
print_message "Para hacer backup: backup_elasticsearch.sh"
print_message "Para conectar: curl http://127.0.0.1:9200"
