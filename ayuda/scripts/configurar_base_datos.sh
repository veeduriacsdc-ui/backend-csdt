#!/bin/bash

# ===========================================
# SCRIPT DE CONFIGURACIÓN DE BASE DE DATOS
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

print_header "CONFIGURACIÓN DE BASE DE DATOS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# CONFIGURAR MYSQL
# ===========================================
print_message "Configurando MySQL..."

# Crear archivo de configuración MySQL
cat > /etc/mysql/mysql.conf.d/csdt.cnf << 'EOF'
[mysqld]
# Configuración optimizada para CSDT
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
max_connections = 200
query_cache_size = 32M
query_cache_type = 1
tmp_table_size = 64M
max_heap_table_size = 64M
key_buffer_size = 32M
read_buffer_size = 2M
read_rnd_buffer_size = 8M
sort_buffer_size = 2M
join_buffer_size = 2M
thread_cache_size = 8
table_open_cache = 4000
open_files_limit = 4000
max_allowed_packet = 64M
wait_timeout = 600
interactive_timeout = 600
EOF

# Reiniciar MySQL
systemctl restart mysql

print_success "✅ MySQL configurado"

# ===========================================
# CREAR BASE DE DATOS
# ===========================================
print_message "Creando base de datos..."

# Crear base de datos y usuario
mysql -u root -p123 << 'EOF'
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario
CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY '123';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar
SHOW DATABASES;
SELECT User, Host FROM mysql.user WHERE User = 'csdt';
EOF

print_success "✅ Base de datos creada"

# ===========================================
# EJECUTAR MIGRACIONES
# ===========================================
print_message "Ejecutando migraciones..."

cd /var/www/backend-csdt

# Ejecutar migraciones
php artisan migrate --force

print_success "✅ Migraciones ejecutadas"

# ===========================================
# EJECUTAR SEEDERS
# ===========================================
print_message "Ejecutando seeders..."

# Ejecutar seeders
php artisan db:seed --force

print_success "✅ Seeders ejecutados"

# ===========================================
# CREAR USUARIOS DE PRUEBA
# ===========================================
print_message "Creando usuarios de prueba..."

# Crear usuarios de prueba
mysql -u csdt -p123 csdt_final << 'EOF'
-- Usuario administrador
INSERT IGNORE INTO users (id, name, email, email_verified_at, password, created_at, updated_at) 
VALUES (1, 'Administrador', 'admin@csdt.com', NOW(), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW(), NOW());

-- Usuario operador
INSERT IGNORE INTO users (id, name, email, email_verified_at, password, created_at, updated_at) 
VALUES (2, 'Operador', 'operador@csdt.com', NOW(), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW(), NOW());

-- Usuario cliente
INSERT IGNORE INTO users (id, name, email, email_verified_at, password, created_at, updated_at) 
VALUES (3, 'Cliente', 'cliente@csdt.com', NOW(), '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/4QzQK2', NOW(), NOW());
EOF

print_success "✅ Usuarios de prueba creados"

# ===========================================
# CONFIGURAR BACKUP AUTOMÁTICO
# ===========================================
print_message "Configurando backup automático..."

# Crear script de backup
cat > /usr/local/bin/backup_database.sh << 'EOF'
#!/bin/bash
# Script de backup automático de base de datos

BACKUP_DIR="/var/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/csdt_database_$DATE.sql"

# Crear directorio si no existe
mkdir -p "$BACKUP_DIR"

# Hacer backup
mysqldump -u csdt -p123 csdt_final > "$BACKUP_FILE"

# Comprimir
gzip "$BACKUP_FILE"

# Eliminar backups antiguos (más de 7 días)
find "$BACKUP_DIR" -name "csdt_database_*.sql.gz" -mtime +7 -delete

echo "Backup completado: $BACKUP_FILE.gz"
EOF

chmod +x /usr/local/bin/backup_database.sh

# Configurar cron para backup diario
echo "0 2 * * * /usr/local/bin/backup_database.sh >> /var/log/csdt_backup.log 2>&1" | crontab -

print_success "✅ Backup automático configurado"

# ===========================================
# OPTIMIZAR BASE DE DATOS
# ===========================================
print_message "Optimizando base de datos..."

# Optimizar tablas
mysql -u csdt -p123 csdt_final << 'EOF'
-- Optimizar tablas principales
OPTIMIZE TABLE users;
OPTIMIZE TABLE password_reset_tokens;
OPTIMIZE TABLE failed_jobs;
OPTIMIZE TABLE personal_access_tokens;
OPTIMIZE TABLE migrations;
OPTIMIZE TABLE sessions;

-- Verificar estado
SHOW TABLE STATUS;
EOF

print_success "✅ Base de datos optimizada"

# ===========================================
# CONFIGURAR MONITOREO
# ===========================================
print_message "Configurando monitoreo..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_database.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de base de datos

echo "=== MONITOR DE BASE DE DATOS ==="
echo "Fecha: $(date)"
echo ""

# Verificar conexión
if mysql -u csdt -p123 -e "SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Conexión a base de datos exitosa"
else
    echo "❌ Error de conexión a base de datos"
    exit 1
fi

# Mostrar estadísticas
echo "=== ESTADÍSTICAS ==="
mysql -u csdt -p123 csdt_final -e "
SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'csdt_final'
ORDER BY (data_length + index_length) DESC;
"

# Mostrar procesos activos
echo "=== PROCESOS ACTIVOS ==="
mysql -u csdt -p123 csdt_final -e "SHOW PROCESSLIST;"

# Mostrar variables importantes
echo "=== VARIABLES IMPORTANTES ==="
mysql -u csdt -p123 csdt_final -e "
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
SHOW VARIABLES LIKE 'query_cache_size';
"
EOF

chmod +x /usr/local/bin/monitor_database.sh

print_success "✅ Monitoreo configurado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_message "Verificando configuración..."

# Verificar conexión
if mysql -u csdt -p123 -e "SELECT 1;" > /dev/null 2>&1; then
    print_success "✅ Conexión a base de datos exitosa"
else
    print_error "❌ Error de conexión a base de datos"
    exit 1
fi

# Verificar tablas
TABLES=$(mysql -u csdt -p123 csdt_final -e "SHOW TABLES;" | wc -l)
print_message "Número de tablas: $TABLES"

# Verificar usuarios
USERS=$(mysql -u csdt -p123 csdt_final -e "SELECT COUNT(*) FROM users;" | tail -1)
print_message "Número de usuarios: $USERS"

# Verificar configuración MySQL
print_message "Configuración MySQL:"
mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'max_connections';"
mysql -u csdt -p123 csdt_final -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size';"

print_success "✅ Configuración verificada"

print_header "CONFIGURACIÓN DE BASE DE DATOS COMPLETADA"

print_success "✅ Base de datos configurada correctamente"
print_message "Base de datos: csdt_final"
print_message "Usuario: csdt"
print_message "Contraseña: 123"
print_message "Host: localhost"
print_message "Puerto: 3306"
print_message "Para monitorear, ejecuta: /usr/local/bin/monitor_database.sh"
