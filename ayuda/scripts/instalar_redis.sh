#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE REDIS
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

print_header "INSTALACIÓN DE REDIS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR REDIS
# ===========================================
print_message "Instalando Redis..."

# Actualizar paquetes
apt update

# Instalar Redis
apt install -y redis-server

print_success "✅ Redis instalado"

# ===========================================
# CONFIGURAR REDIS
# ===========================================
print_message "Configurando Redis..."

# Hacer backup de la configuración
cp /etc/redis/redis.conf /etc/redis/redis.conf.backup

# Configurar Redis
cat > /etc/redis/redis.conf << 'EOF'
# Configuración Redis para CSDT
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300
tcp-backlog 511

# Configuración de persistencia
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis

# Configuración de memoria
maxmemory 256mb
maxmemory-policy allkeys-lru

# Configuración de logs
loglevel notice
logfile /var/log/redis/redis-server.log

# Configuración de seguridad
requirepass redis_password_2024

# Configuración de red
tcp-keepalive 300
tcp-backlog 511

# Configuración de cliente
timeout 0
tcp-keepalive 300

# Configuración de base de datos
databases 16

# Configuración de persistencia
save 900 1
save 300 10
save 60 10000

# Configuración de logs
loglevel notice
logfile /var/log/redis/redis-server.log

# Configuración de seguridad
requirepass redis_password_2024
EOF

print_success "✅ Redis configurado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

# Crear directorio de logs
mkdir -p /var/log/redis
chown redis:redis /var/log/redis

# Configurar permisos
chown redis:redis /var/lib/redis
chmod 755 /var/lib/redis

print_success "✅ Permisos configurados"

# ===========================================
# INICIAR REDIS
# ===========================================
print_message "Iniciando Redis..."

# Habilitar Redis
systemctl enable redis-server
systemctl start redis-server

print_success "✅ Redis iniciado"

# ===========================================
# CONFIGURAR PHP PARA REDIS
# ===========================================
print_message "Configurando PHP para Redis..."

# Instalar extensión Redis para PHP
apt install -y php8.2-redis

# Configurar PHP para usar Redis
cat >> /etc/php/8.2/fpm/php.ini << 'EOF'
; Configuración Redis para CSDT
extension=redis.so
EOF

# Reiniciar PHP-FPM
systemctl restart php8.2-fpm

print_success "✅ PHP configurado para Redis"

# ===========================================
# CONFIGURAR LARAVEL PARA REDIS
# ===========================================
print_message "Configurando Laravel para Redis..."

# Actualizar .env del backend
cd /var/www/backend-csdt

# Agregar configuración de Redis
cat >> .env << 'EOF'

# Configuración de Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password_2024
REDIS_PORT=6379

# Configuración de cache con Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
EOF

print_success "✅ Laravel configurado para Redis"

# ===========================================
# CONFIGURAR MONITOREO DE REDIS
# ===========================================
print_message "Configurando monitoreo de Redis..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_redis.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Redis

echo "=== MONITOR DE REDIS ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de Redis
echo "Estado de Redis:"
systemctl status redis-server --no-pager

# Verificar conexión
echo "Conexión a Redis:"
redis-cli -a redis_password_2024 ping

# Verificar información
echo "Información de Redis:"
redis-cli -a redis_password_2024 info server

# Verificar memoria
echo "Uso de memoria:"
redis-cli -a redis_password_2024 info memory

# Verificar base de datos
echo "Información de base de datos:"
redis-cli -a redis_password_2024 info keyspace

# Verificar logs
echo "Últimas 5 líneas del log:"
tail -5 /var/log/redis/redis-server.log
EOF

chmod +x /usr/local/bin/monitor_redis.sh

print_success "✅ Monitoreo de Redis configurado"

# ===========================================
# CONFIGURAR BACKUP DE REDIS
# ===========================================
print_message "Configurando backup de Redis..."

# Crear script de backup
cat > /usr/local/bin/backup_redis.sh << 'EOF'
#!/bin/bash
# Script de backup de Redis

BACKUP_DIR="/var/backups/redis_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Haciendo backup de Redis..."

# Backup de datos
redis-cli -a redis_password_2024 BGSAVE
sleep 5
cp /var/lib/redis/dump.rdb "$BACKUP_DIR/"

# Backup de configuración
cp /etc/redis/redis.conf "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "redis_$(date +%Y%m%d_%H%M%S).tar.gz" "redis_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de Redis completado"
EOF

chmod +x /usr/local/bin/backup_redis.sh

print_success "✅ Backup de Redis configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE REDIS
# ===========================================
print_message "Configurando cron jobs de Redis..."

# Backup de Redis diario
echo "0 2 * * * /usr/local/bin/backup_redis.sh >> /var/log/csdt_redis_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de Redis configurados"

# ===========================================
# CONFIGURAR FIREWALL PARA REDIS
# ===========================================
print_message "Configurando firewall para Redis..."

# Redis solo debe ser accesible localmente
# No abrir puerto 6379 al exterior por seguridad

print_success "✅ Firewall configurado para Redis"

# ===========================================
# VERIFICAR INSTALACIÓN DE REDIS
# ===========================================
print_message "Verificando instalación de Redis..."

# Verificar estado
systemctl status redis-server --no-pager

# Verificar conexión
if redis-cli -a redis_password_2024 ping | grep -q "PONG"; then
    print_success "✅ Redis funcionando correctamente"
else
    print_warning "⚠️ Redis no responde"
fi

# Verificar información
redis-cli -a redis_password_2024 info server | head -10

print_success "✅ Instalación de Redis verificada"

print_header "INSTALACIÓN DE REDIS COMPLETADA"

print_success "✅ Redis instalado correctamente"
print_message "Host: 127.0.0.1"
print_message "Puerto: 6379"
print_message "Contraseña: redis_password_2024"
print_message "Para monitorear: monitor_redis.sh"
print_message "Para hacer backup: backup_redis.sh"
print_message "Para conectar: redis-cli -a redis_password_2024"
