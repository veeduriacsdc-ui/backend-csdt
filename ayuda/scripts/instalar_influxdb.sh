#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE INFLUXDB
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

print_header "INSTALACIÓN DE INFLUXDB"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR INFLUXDB
# ===========================================
print_message "Instalando InfluxDB..."

# Agregar clave GPG de InfluxDB
wget -qO- https://repos.influxdata.com/influxdb.key | apt-key add -

# Agregar repositorio de InfluxDB
echo "deb https://repos.influxdata.com/ubuntu focal stable" | tee /etc/apt/sources.list.d/influxdb.list

# Actualizar paquetes
apt update

# Instalar InfluxDB
apt install -y influxdb

print_success "✅ InfluxDB instalado"

# ===========================================
# CONFIGURAR INFLUXDB
# ===========================================
print_message "Configurando InfluxDB..."

# Hacer backup de la configuración
cp /etc/influxdb/influxdb.conf /etc/influxdb/influxdb.conf.backup

# Configurar InfluxDB
cat > /etc/influxdb/influxdb.conf << 'EOF'
# Configuración InfluxDB para CSDT
[meta]
  dir = "/var/lib/influxdb/meta"
  retention-autocreate = true
  logging-enabled = true

[data]
  dir = "/var/lib/influxdb/data"
  wal-dir = "/var/lib/influxdb/wal"
  query-log-enabled = true
  cache-max-memory-size = "1g"
  cache-snapshot-memory-size = "25m"
  cache-snapshot-write-cold-duration = "10m"
  compact-full-write-cold-duration = "4h"
  max-series-per-database = 1000000
  max-values-per-tag = 100000
  trace-logging-enabled = false

[coordinator]
  write-timeout = "10s"
  max-concurrent-queries = 0
  query-timeout = "0s"
  log-queries-after = "0s"
  max-select-point = 0
  max-select-series = 0
  max-select-buckets = 0

[retention]
  enabled = true
  check-interval = "30m"

[shard-precreation]
  enabled = true
  check-interval = "10m"
  advance-period = "30m"

[monitor]
  store-enabled = true
  store-database = "_internal"
  store-interval = "10s"

[admin]
  enabled = true
  bind-address = ":8083"
  https-enabled = false
  https-certificate = "/etc/ssl/influxdb.pem"

[subscriber]
  enabled = true
  http-timeout = "30s"
  insecure-skip-verify = false
  ca-certs = ""
  write-concurrency = 40
  write-buffer-size = 1000

[http]
  enabled = true
  bind-address = ":8086"
  auth-enabled = false
  log-enabled = true
  write-tracing = false
  pprof-enabled = true
  pprof-auth-enabled = false
  debug-pprof-enabled = false
  https-enabled = false
  https-certificate = "/etc/ssl/influxdb.pem"
  https-private-key = ""
  max-body-size = 25000000
  shared-secret = ""
  max-concurrent-write-limit = 0
  max-enqueued-write-limit = 0
  enqueued-write-timeout = 30000000000

[logging]
  format = "auto"
  level = "info"
  suppress-logo = false

[continuous_queries]
  log-enabled = true
  enabled = true
  query-stats-enabled = false
  run-interval = "1s"
  max-processes = 0
  max-query-duration = "0s"
  always-log-query-stats = false
