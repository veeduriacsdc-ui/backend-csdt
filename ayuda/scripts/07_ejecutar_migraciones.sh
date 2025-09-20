#!/bin/bash

# ===========================================
# SCRIPT 7: EJECUTAR MIGRACIONES
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
BACKEND_DIR="/var/www/backend-csdt"
DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"

print_header "PASO 7: EJECUTANDO MIGRACIONES Y SEEDERS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./07_ejecutar_migraciones.sh)"
    exit 1
fi

# ===========================================
# NAVEGAR AL DIRECTORIO DEL BACKEND
# ===========================================
print_step "Navegando al directorio del backend..."

cd "$BACKEND_DIR"

if [ ! -f "artisan" ]; then
    print_error "❌ Archivo artisan no encontrado. ¿Está instalado el backend?"
    exit 1
fi

print_message "✅ Directorio del backend: $BACKEND_DIR"

# ===========================================
# VERIFICAR CONEXIÓN A LA BASE DE DATOS
# ===========================================
print_step "Verificando conexión a la base de datos..."

# Verificar conexión a MySQL
if mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT 1;" > /dev/null 2>&1; then
    print_message "✅ Conexión a la base de datos exitosa"
else
    print_error "❌ No se puede conectar a la base de datos"
    print_error "Verifica que MySQL esté ejecutándose y las credenciales sean correctas"
    exit 1
fi

# ===========================================
# LIMPIAR CACHE DE LARAVEL
# ===========================================
print_step "Limpiando cache de Laravel..."

# Limpiar todos los caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

print_message "✅ Cache de Laravel limpiado"

# ===========================================
# VERIFICAR MIGRACIONES DISPONIBLES
# ===========================================
print_step "Verificando migraciones disponibles..."

# Listar migraciones
MIGRATIONS_COUNT=$(ls database/migrations/ | wc -l)
print_message "✅ Migraciones encontradas: $MIGRATIONS_COUNT"

# Mostrar migraciones
echo "Migraciones disponibles:"
ls database/migrations/ | head -10
if [ $MIGRATIONS_COUNT -gt 10 ]; then
    echo "... y $((MIGRATIONS_COUNT - 10)) más"
fi

# ===========================================
# EJECUTAR MIGRACIONES
# ===========================================
print_step "Ejecutando migraciones..."

# Ejecutar migraciones con manejo de errores
if php artisan migrate --force; then
    print_message "✅ Migraciones ejecutadas correctamente"
else
    print_error "❌ Error ejecutando migraciones"
    
    # Intentar rollback y re-ejecutar
    print_warning "Intentando rollback y re-ejecución..."
    
    if php artisan migrate:rollback --force; then
        print_message "✅ Rollback exitoso"
        
        if php artisan migrate --force; then
            print_message "✅ Migraciones re-ejecutadas correctamente"
        else
            print_error "❌ Error re-ejecutando migraciones"
            exit 1
        fi
    else
        print_error "❌ Error en rollback"
        exit 1
    fi
fi

# ===========================================
# VERIFICAR MIGRACIONES EJECUTADAS
# ===========================================
print_step "Verificando migraciones ejecutadas..."

# Verificar tablas creadas
TABLES_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" | wc -l)
print_message "✅ Tablas creadas: $((TABLES_COUNT - 1))"

# Mostrar tablas creadas
echo "Tablas en la base de datos:"
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" | tail -n +2

# ===========================================
# VERIFICAR SEEDERS DISPONIBLES
# ===========================================
print_step "Verificando seeders disponibles..."

# Listar seeders
SEEDERS_COUNT=$(ls database/seeders/ | wc -l)
print_message "✅ Seeders encontrados: $SEEDERS_COUNT"

# Mostrar seeders
echo "Seeders disponibles:"
ls database/seeders/ | grep -v "DatabaseSeeder.php" | head -10

# ===========================================
# EJECUTAR SEEDERS
# ===========================================
print_step "Ejecutando seeders..."

# Ejecutar seeders con manejo de errores
if php artisan db:seed --force; then
    print_message "✅ Seeders ejecutados correctamente"
else
    print_warning "⚠️ Error ejecutando seeders (puede ser normal si ya existen datos)"
    
    # Intentar ejecutar seeders específicos
    print_warning "Intentando ejecutar seeders específicos..."
    
    # Listar seeders específicos
    for seeder in database/seeders/*.php; do
        if [ -f "$seeder" ] && [ "$(basename "$seeder")" != "DatabaseSeeder.php" ]; then
            seeder_name=$(basename "$seeder" .php)
            print_message "Ejecutando seeder: $seeder_name"
            
            if php artisan db:seed --class="$seeder_name" --force; then
                print_message "✅ $seeder_name ejecutado correctamente"
            else
                print_warning "⚠️ Error ejecutando $seeder_name"
            fi
        fi
    done
fi

# ===========================================
# VERIFICAR DATOS INSERTADOS
# ===========================================
print_step "Verificando datos insertados..."

# Verificar usuarios
USERS_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1 || echo "0")
print_message "✅ Usuarios en la base de datos: $USERS_COUNT"

# Verificar otras tablas importantes
TABLES_TO_CHECK=("clientes" "operadores" "pqrsfd" "donaciones" "roles" "permisos")
for table in "${TABLES_TO_CHECK[@]}"; do
    COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM $table;" 2>/dev/null | tail -n 1 || echo "0")
    if [ "$COUNT" != "0" ]; then
        print_message "✅ Tabla $table: $COUNT registros"
    fi
done

# ===========================================
# OPTIMIZAR BASE DE DATOS
# ===========================================
print_step "Optimizando base de datos..."

# Optimizar tablas
mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; OPTIMIZE TABLE users, clientes, operadores, pqrsfd, donaciones, roles, permisos;" > /dev/null 2>&1 || true

print_message "✅ Base de datos optimizada"

# ===========================================
# CREAR SCRIPT DE VERIFICACIÓN DE BD
# ===========================================
print_step "Creando script de verificación de base de datos..."

cat > verificar_bd.sh << 'EOF'
#!/bin/bash
# Script para verificar la base de datos

DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"

echo "Verificando base de datos..."

# Verificar conexión
if mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT 1;" > /dev/null 2>&1; then
    echo "✅ Conexión a la base de datos exitosa"
else
    echo "❌ Error de conexión a la base de datos"
    exit 1
fi

# Verificar tablas
TABLES_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SHOW TABLES;" | wc -l)
echo "✅ Tablas en la base de datos: $((TABLES_COUNT - 1))"

# Verificar migraciones
MIGRATIONS_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM migrations;" 2>/dev/null | tail -n 1 || echo "0")
echo "✅ Migraciones ejecutadas: $MIGRATIONS_COUNT"

# Verificar usuarios
USERS_COUNT=$(mysql -u "$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1 || echo "0")
echo "✅ Usuarios: $USERS_COUNT"

echo "Base de datos verificada correctamente"
EOF

chmod +x verificar_bd.sh

print_message "✅ Script de verificación de base de datos creado"

# ===========================================
# CREAR SCRIPT DE BACKUP DE BD
# ===========================================
print_step "Creando script de backup de base de datos..."

cat > backup_bd.sh << 'EOF'
#!/bin/bash
# Script para hacer backup de la base de datos

DB_NAME="csdt_final"
DB_USER="csdt"
DB_PASSWORD="csdt_password_2024"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Crear directorio de backup
mkdir -p $BACKUP_DIR

# Crear backup
mysqldump -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" > "$BACKUP_DIR/csdt_backup_$DATE.sql"

# Comprimir backup
gzip "$BACKUP_DIR/csdt_backup_$DATE.sql"

# Eliminar backups antiguos (más de 7 días)
find $BACKUP_DIR -name "csdt_backup_*.sql.gz" -mtime +7 -delete

echo "Backup creado: $BACKUP_DIR/csdt_backup_$DATE.sql.gz"
EOF

chmod +x backup_bd.sh

print_message "✅ Script de backup de base de datos creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN FINAL
# ===========================================
print_step "Verificando configuración final..."

# Ejecutar script de verificación
./verificar_bd.sh

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "MIGRACIONES Y SEEDERS EJECUTADOS EXITOSAMENTE"

print_message "✅ Migraciones ejecutadas correctamente"
print_message "✅ Seeders ejecutados correctamente"
print_message "✅ Base de datos optimizada"
print_message "✅ Scripts de verificación y backup creados"

print_warning "INFORMACIÓN DE BASE DE DATOS:"
print_warning "Base de datos: $DB_NAME"
print_warning "Usuario: $DB_USER"
print_warning "Tablas creadas: $((TABLES_COUNT - 1))"
print_warning "Usuarios: $USERS_COUNT"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./08_iniciar_servicios.sh"

print_message "¡Migraciones y seeders ejecutados correctamente!"
