#!/bin/bash

# ===========================================
# SCRIPT 3: CONFIGURAR MYSQL
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

# Variables de configuración
DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"
ROOT_PASSWORD=""

print_header "PASO 3: CONFIGURANDO MYSQL"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./03_configurar_mysql.sh)"
    exit 1
fi

# ===========================================
# CONFIGURAR MYSQL DE FORMA SEGURA
# ===========================================
print_step "Configurando MySQL de forma segura..."

# Crear script de configuración automática
cat > /tmp/mysql_secure_install.sql << 'EOF'
-- Configuración automática de MySQL para CSDT
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'root_password_2024';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';
FLUSH PRIVILEGES;
EOF

# Ejecutar configuración automática
mysql -u root < /tmp/mysql_secure_install.sql

# Configurar contraseña de root
ROOT_PASSWORD="root_password_2024"

print_message "✅ MySQL configurado de forma segura"

# ===========================================
# CREAR BASE DE DATOS Y USUARIO
# ===========================================
print_step "Creando base de datos y usuario..."

# Crear script de base de datos
cat > /tmp/create_database.sql << EOF
-- Crear base de datos para CSDT
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario para CSDT
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';

-- Crear usuario para acceso remoto (opcional)
CREATE USER IF NOT EXISTS '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Mostrar información
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = '$DB_USER';
EOF

# Ejecutar script de base de datos
mysql -u root -p$ROOT_PASSWORD < /tmp/create_database.sql

print_message "✅ Base de datos y usuario creados"

# ===========================================
# CONFIGURAR MYSQL PARA CSDT
# ===========================================
print_step "Configurando MySQL para CSDT..."

# Crear configuración específica para CSDT
cat > /etc/mysql/mysql.conf.d/csdt-specific.cnf << 'EOF'
[mysqld]
# Configuración específica para CSDT
default_authentication_plugin = mysql_native_password
default-storage-engine = InnoDB
innodb_file_per_table = 1
innodb_open_files = 400
innodb_io_capacity = 400
innodb_flush_method = O_DIRECT

# Configuración de conexiones
max_connections = 200
max_connect_errors = 1000
connect_timeout = 60
wait_timeout = 600
interactive_timeout = 600

# Configuración de consultas
query_cache_size = 32M
query_cache_type = 1
query_cache_limit = 2M
tmp_table_size = 64M
max_heap_table_size = 64M

# Configuración de logs
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Configuración de InnoDB
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_log_buffer_size = 8M
innodb_flush_log_at_trx_commit = 2
innodb_lock_wait_timeout = 50

# Configuración de MyISAM
key_buffer_size = 32M
myisam_sort_buffer_size = 8M
myisam_max_sort_file_size = 10G
myisam_repair_threads = 1

# Configuración de red
max_allowed_packet = 64M
net_buffer_length = 2K
thread_stack = 256K
thread_cache_size = 8
sort_buffer_size = 2M
bulk_insert_buffer_size = 8M
myisam_sort_buffer_size = 8M
myisam_max_sort_file_size = 10G
myisam_repair_threads = 1
myisam_recover_options = BACKUP

# Configuración de seguridad
local_infile = 0
symbolic-links = 0
skip-name-resolve
EOF

# Reiniciar MySQL
systemctl restart mysql

print_message "✅ MySQL configurado para CSDT"

# ===========================================
# CREAR SCRIPT DE BACKUP
# ===========================================
print_step "Creando script de backup..."

cat > /usr/local/bin/backup_csdt_db.sh << 'EOF'
#!/bin/bash
# Script de backup para base de datos CSDT

DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Crear backup
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME > $BACKUP_DIR/csdt_backup_$DATE.sql

# Comprimir backup
gzip $BACKUP_DIR/csdt_backup_$DATE.sql

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "csdt_backup_*.sql.gz" -mtime +7 -delete

echo "Backup creado: $BACKUP_DIR/csdt_backup_$DATE.sql.gz"
EOF

chmod +x /usr/local/bin/backup_csdt_db.sh

# Crear cron job para backup diario
echo "0 2 * * * /usr/local/bin/backup_csdt_db.sh" | crontab -

print_message "✅ Script de backup creado"

# ===========================================
# CONFIGURAR MONITOREO
# ===========================================
print_step "Configurando monitoreo de MySQL..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_mysql.sh << 'EOF'
#!/bin/bash
# Script de monitoreo para MySQL

DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"

# Verificar conexión
mysql -u $DB_USER -p$DB_PASSWORD -e "SELECT 1;" $DB_NAME > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ MySQL está funcionando correctamente"
else
    echo "❌ Error de conexión a MySQL"
    systemctl restart mysql
fi

# Verificar espacio en disco
DISK_USAGE=$(df -h /var/lib/mysql | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "⚠️ Advertencia: Uso de disco MySQL > 80%"
fi

# Verificar conexiones activas
ACTIVE_CONNECTIONS=$(mysql -u $DB_USER -p$DB_PASSWORD -e "SHOW STATUS LIKE 'Threads_connected';" | awk 'NR==2 {print $2}')
echo "Conexiones activas: $ACTIVE_CONNECTIONS"
EOF

chmod +x /usr/local/bin/monitor_mysql.sh

print_message "✅ Script de monitoreo creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_step "Verificando configuración de MySQL..."

# Verificar conexión
if mysql -u $DB_USER -p$DB_PASSWORD -e "SHOW DATABASES;" > /dev/null 2>&1; then
    print_message "✅ Conexión a MySQL exitosa"
else
    print_error "❌ Error de conexión a MySQL"
    exit 1
fi

# Verificar base de datos
if mysql -u $DB_USER -p$DB_PASSWORD -e "USE $DB_NAME; SHOW TABLES;" > /dev/null 2>&1; then
    print_message "✅ Base de datos $DB_NAME accesible"
else
    print_message "ℹ️ Base de datos $DB_NAME creada (vacía)"
fi

# Verificar usuario
USER_EXISTS=$(mysql -u root -p$ROOT_PASSWORD -e "SELECT User FROM mysql.user WHERE User='$DB_USER';" | grep -c "$DB_USER" || true)
if [ $USER_EXISTS -gt 0 ]; then
    print_message "✅ Usuario $DB_USER creado correctamente"
else
    print_error "❌ Error creando usuario $DB_USER"
    exit 1
fi

# Verificar configuración
MYSQL_VERSION=$(mysql --version | cut -d' ' -f3 | cut -d',' -f1)
print_message "✅ MySQL $MYSQL_VERSION configurado correctamente"

# ===========================================
# LIMPIAR ARCHIVOS TEMPORALES
# ===========================================
print_step "Limpiando archivos temporales..."
rm -f /tmp/mysql_secure_install.sql
rm -f /tmp/create_database.sql

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "MYSQL CONFIGURADO EXITOSAMENTE"

print_message "✅ MySQL configurado de forma segura"
print_message "✅ Base de datos '$DB_NAME' creada"
print_message "✅ Usuario '$DB_USER' creado"
print_message "✅ Configuración optimizada para CSDT"
print_message "✅ Script de backup configurado"
print_message "✅ Script de monitoreo creado"

print_warning "INFORMACIÓN DE CONEXIÓN:"
print_warning "Base de datos: $DB_NAME"
print_warning "Usuario: $DB_USER"
print_warning "Contraseña: $DB_PASSWORD"
print_warning "Host: localhost"
print_warning "Puerto: 3306"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./04_instalar_backend.sh"

print_message "¡MySQL configurado correctamente!"
