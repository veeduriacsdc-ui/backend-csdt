#!/bin/bash

# ===========================================
# SCRIPT DE ORGANIZACIÓN Y LIMPIEZA
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

print_header "ORGANIZACIÓN Y LIMPIEZA DE SCRIPTS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}[ERROR]${NC} Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# HACER SCRIPTS EJECUTABLES
# ===========================================
print_message "Haciendo scripts ejecutables..."

# Scripts principales
chmod +x /var/www/backend-csdt/ayuda/*.sh

# Scripts específicos
chmod +x /var/www/backend-csdt/ayuda/scripts/*.sh

# Scripts del frontend
chmod +x /var/www/frontend-csdt/ayuda/*.sh

# Scripts globales
chmod +x /usr/local/bin/*.sh

print_message "✅ Scripts hechos ejecutables"

# ===========================================
# CREAR ÍNDICE DE SCRIPTS
# ===========================================
print_message "Creando índice de scripts..."

cat > /var/www/README_SCRIPTS.md << 'EOF'
# ÍNDICE DE SCRIPTS CSDT

## Scripts Principales

### 🚀 Instalación Completa
- `instalar_csdt_completo_mejorado.sh` - Instalación completa automatizada
- `instalar_csdt_digitalocean_mejorado.sh` - Instalación básica mejorada

### 🔧 Gestión Básica
- `actualizar_csdt_completo.sh` - Actualizar backend y frontend
- `reparar_csdt.sh` - Reparar sistema
- `verificar_csdt.sh` - Verificar estado
- `limpiar_csdt.sh` - Limpiar y optimizar
- `backup_csdt.sh` - Hacer backup
- `restaurar_csdt.sh` - Restaurar backup
- `monitor_csdt.sh` - Monitoreo en tiempo real
- `diagnosticar_csdt.sh` - Diagnóstico completo

### 🔨 Instalación de Componentes
- `instalar_librerias.sh` - Instalar librerías
- `configurar_base_datos.sh` - Configurar MySQL
- `instalar_servicios_ia.sh` - Instalar servicios de IA
- `configurar_seguridad.sh` - Configurar seguridad

### 🌐 Servicios Web
- `configurar_nginx.sh` - Configurar Nginx
- `instalar_ssl.sh` - Instalar certificados SSL

### 🐳 Docker (Opcional)
- `instalar_docker.sh` - Instalar Docker
- Scripts de Docker en `/usr/local/bin/`

### 📊 Monitoreo (Opcional)
- `instalar_prometheus.sh` - Instalar Prometheus
- `instalar_grafana.sh` - Instalar Grafana
- `instalar_node_exporter.sh` - Instalar Node Exporter
- `instalar_mysql_exporter.sh` - Instalar MySQL Exporter
- `instalar_nginx_exporter.sh` - Instalar Nginx Exporter
- `instalar_redis_exporter.sh` - Instalar Redis Exporter
- `instalar_alertmanager.sh` - Instalar Alertmanager
- `instalar_pushgateway.sh` - Instalar Pushgateway
- `instalar_blackbox_exporter.sh` - Instalar Blackbox Exporter
- `instalar_cadvisor.sh` - Instalar cAdvisor

### 💾 Base de Datos (Opcional)
- `instalar_redis.sh` - Instalar Redis
- `instalar_elasticsearch.sh` - Instalar Elasticsearch
- `instalar_kibana.sh` - Instalar Kibana
- `instalar_logstash.sh` - Instalar Logstash
- `instalar_influxdb.sh` - Instalar InfluxDB
- `instalar_telegraf.sh` - Instalar Telegraf
- `instalar_chronograf.sh` - Instalar Chronograf

### 🧹 Mantenimiento
- `limpiar_archivos_temporales.sh` - Limpiar archivos temporales
- `actualizar_sistema.sh` - Actualizar sistema completo
- `organizar_scripts.sh` - Organizar scripts

## Scripts Globales (en /usr/local/bin/)
- `gestionar_csdt.sh` - Menú interactivo de gestión
- `iniciar_csdt.sh` - Inicio rápido
- `detener_csdt.sh` - Parada rápida
- `verificar_csdt.sh` - Verificación rápida

## Uso Recomendado

### 1. Instalación Inicial
```bash
sudo ./instalar_csdt_completo_mejorado.sh
```

### 2. Gestión Diaria
```bash
gestionar_csdt.sh
```

### 3. Monitoreo
```bash
monitor_csdt.sh
```

### 4. Mantenimiento
```bash
sudo ./actualizar_sistema.sh
sudo ./limpiar_archivos_temporales.sh
```

## Notas Importantes
- Todos los scripts deben ejecutarse como root (sudo)
- Los scripts están optimizados para Ubuntu en DigitalOcean
- La configuración usa IP directa sin dominio
- Los servicios opcionales pueden instalarse según necesidades
EOF

print_message "✅ Índice de scripts creado"

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES
# ===========================================
print_message "Limpiando archivos temporales..."

# Limpiar archivos de instalación
rm -rf /tmp/prometheus-*
rm -rf /tmp/node_exporter-*
rm -rf /tmp/mysqld_exporter-*
rm -rf /tmp/nginx-prometheus-exporter-*
rm -rf /tmp/redis_exporter-*
rm -rf /tmp/alertmanager-*
rm -rf /tmp/pushgateway-*
rm -rf /tmp/blackbox_exporter-*
rm -rf /tmp/cadvisor-*

print_message "✅ Archivos temporales limpiados"

# ===========================================
# VERIFICAR ESTRUCTURA
# ===========================================
print_message "Verificando estructura..."

echo "Scripts en backend-csdt/ayuda:"
ls -la /var/www/backend-csdt/ayuda/*.sh | wc -l

echo "Scripts en backend-csdt/ayuda/scripts:"
ls -la /var/www/backend-csdt/ayuda/scripts/*.sh | wc -l

echo "Scripts globales:"
ls -la /usr/local/bin/*csdt*.sh | wc -l

print_message "✅ Estructura verificada"

print_header "ORGANIZACIÓN COMPLETADA"

print_message "✅ Scripts organizados y listos para usar"
print_message "Consulta README_SCRIPTS.md para más información"
