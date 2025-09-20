#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE TELEGRAF
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

print_header "INSTALACIÓN DE TELEGRAF"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR TELEGRAF
# ===========================================
print_message "Instalando Telegraf..."

# Agregar clave GPG de Telegraf
wget -qO- https://repos.influxdata.com/influxdb.key | apt-key add -

# Agregar repositorio de Telegraf
echo "deb https://repos.influxdata.com/ubuntu focal stable" | tee /etc/apt/sources.list.d/telegraf.list

# Actualizar paquetes
apt update

# Instalar Telegraf
apt install -y telegraf

print_success "✅ Telegraf instalado"

# ===========================================
# CONFIGURAR TELEGRAF
# ===========================================
print_message "Configurando Telegraf..."

# Hacer backup de la configuración
cp /etc/telegraf/telegraf.conf /etc/telegraf/telegraf.conf.backup

# Configurar Telegraf
cat > /etc/telegraf/telegraf.conf << 'EOF'
# Configuración Telegraf para CSDT
[global_tags]
  environment = "production"
  service = "csdt"

[agent]
  interval = "10s"
  round_interval = true
  metric_batch_size = 1000
  metric_buffer_limit = 10000
  collection_jitter = "0s"
  flush_interval = "10s"
  flush_jitter = "0s"
  precision = ""
  hostname = ""
  omit_hostname = false

[[outputs.influxdb]]
  urls = ["http://127.0.0.1:8086"]
  database = "csdt_metrics"
  retention_policy = ""
  write_consistency = "any"
  timeout = "5s"
  username = ""
  password = ""

[[inputs.cpu]]
  percpu = true
  totalcpu = true
  collect_cpu_time = false
  report_active = false

[[inputs.disk]]
  ignore_fs = ["tmpfs", "devtmpfs", "devfs", "iso9660", "overlay", "aufs", "squashfs"]

[[inputs.diskio]]

[[inputs.kernel]]

[[inputs.mem]]

[[inputs.processes]]

[[inputs.swap]]

[[inputs.system]]

[[inputs.net]]

[[inputs.netstat]]

[[inputs.interrupts]]

[[inputs.linux_sysctl_fs]]

[[inputs.systemd_units]]
  pattern = ".*"
  detail = false

[[inputs.mysql]]
  servers = ["tcp(127.0.0.1:3306)/"]
  username = "csdt"
  password = "123"
  gather_all = true

[[inputs.nginx]]
  urls = ["http://127.0.0.1/nginx_status"]

[[inputs.redis]]
  servers = ["tcp://127.0.0.1:6379"]
  password = "redis_password_2024"

[[inputs.phpfpm]]
  urls = ["http://127.0.0.1/status"]

[[inputs.prometheus]]
  urls = ["http://127.0.0.1:9090/metrics"]

[[inputs.http_listener]]
  service_address = ":8186"
  data_format = "influx"
