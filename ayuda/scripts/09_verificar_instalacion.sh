#!/bin/bash

# ===========================================
# SCRIPT 9: VERIFICAR INSTALACIÓN
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
IP_PUBLICA="64.225.113.49"
BACKEND_DIR="/var/www/backend-csdt"
FRONTEND_DIR="/var/www/frontend-csdt"

print_header "PASO 9: VERIFICANDO INSTALACIÓN COMPLETA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./09_verificar_instalacion.sh)"
    exit 1
fi

# ===========================================
# VERIFICAR SERVICIOS DEL SISTEMA
# ===========================================
print_step "Verificando servicios del sistema..."

# Verificar PHP
if command -v php > /dev/null 2>&1; then
    PHP_VERSION=$(php --version | head -n1 | cut -d' ' -f2)
    print_message "✅ PHP $PHP_VERSION instalado"
else
    print_error "❌ PHP no está instalado"
fi

# Verificar Composer
if command -v composer > /dev/null 2>&1; then
    COMPOSER_VERSION=$(composer --version | cut -d' ' -f3)
    print_message "✅ Composer $COMPOSER_VERSION instalado"
else
    print_error "❌ Composer no está instalado"
fi

# Verificar Node.js
if command -v node > /dev/null 2>&1; then
    NODE_VERSION=$(node --version | cut -d'v' -f2)
    print_message "✅ Node.js $NODE_VERSION instalado"
else
    print_error "❌ Node.js no está instalado"
fi

# Verificar npm
if command -v npm > /dev/null 2>&1; then
    NPM_VERSION=$(npm --version)
    print_message "✅ npm $NPM_VERSION instalado"
else
    print_error "❌ npm no está instalado"
fi

# Verificar PM2
if command -v pm2 > /dev/null 2>&1; then
    PM2_VERSION=$(pm2 --version)
    print_message "✅ PM2 $PM2_VERSION instalado"
else
    print_error "❌ PM2 no está instalado"
fi

# Verificar MySQL
if command -v mysql > /dev/null 2>&1; then
    MYSQL_VERSION=$(mysql --version | cut -d' ' -f3 | cut -d',' -f1)
    print_message "✅ MySQL $MYSQL_VERSION instalado"
else
    print_error "❌ MySQL no está instalado"
fi

# Verificar Nginx
if command -v nginx > /dev/null 2>&1; then
    NGINX_VERSION=$(nginx -v 2>&1 | cut -d'/' -f2)
    print_message "✅ Nginx $NGINX_VERSION instalado"
else
    print_error "❌ Nginx no está instalado"
fi

# ===========================================
# VERIFICAR ESTADO DE PM2
# ===========================================
print_step "Verificando estado de PM2..."

echo "Estado de aplicaciones PM2:"
pm2 status

# Verificar que los servicios estén corriendo
BACKEND_RUNNING=$(pm2 list | grep "backend-csdt" | grep "online" | wc -l)
FRONTEND_RUNNING=$(pm2 list | grep "frontend-csdt" | grep "online" | wc -l)

if [ $BACKEND_RUNNING -gt 0 ]; then
    print_message "✅ Backend corriendo en PM2"
else
    print_error "❌ Backend no está corriendo en PM2"
fi

if [ $FRONTEND_RUNNING -gt 0 ]; then
    print_message "✅ Frontend corriendo en PM2"
else
    print_error "❌ Frontend no está corriendo en PM2"
fi

# ===========================================
# VERIFICAR CONECTIVIDAD LOCAL
# ===========================================
print_step "Verificando conectividad local..."

# Verificar backend local
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_message "✅ Backend local responde correctamente"
else
    print_warning "⚠️ Backend local no responde"
fi

# Verificar frontend local
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_message "✅ Frontend local responde correctamente"
else
    print_warning "⚠️ Frontend local no responde"
fi

# ===========================================
# VERIFICAR CONECTIVIDAD EXTERNA
# ===========================================
print_step "Verificando conectividad externa..."

# Verificar backend externo
if curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1; then
    print_message "✅ Backend externo accesible"
else
    print_warning "⚠️ Backend externo no accesible"
fi

# Verificar frontend externo
if curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1; then
    print_message "✅ Frontend externo accesible"
else
    print_warning "⚠️ Frontend externo no accesible"
fi

# ===========================================
# VERIFICAR ARCHIVOS DE CONFIGURACIÓN
# ===========================================
print_step "Verificando archivos de configuración..."

# Verificar archivo .env del backend
if [ -f "$BACKEND_DIR/.env" ]; then
    print_message "✅ Archivo .env del backend existe"
    
    # Verificar configuración de base de datos
    if grep -q "DB_CONNECTION=mysql" "$BACKEND_DIR/.env"; then
        print_message "✅ Base de datos configurada como MySQL"
    else
        print_warning "⚠️ Base de datos no configurada como MySQL"
    fi
    
    # Verificar clave de aplicación
    if grep -q "APP_KEY=base64:" "$BACKEND_DIR/.env"; then
        print_message "✅ Clave de aplicación configurada"
    else
        print_warning "⚠️ Clave de aplicación no configurada"
    fi
else
    print_error "❌ Archivo .env del backend no existe"
fi

# Verificar archivo .env del frontend
if [ -f "$FRONTEND_DIR/.env" ]; then
    print_message "✅ Archivo .env del frontend existe"
    
    # Verificar configuración de API
    if grep -q "VITE_API_URL=http://$IP_PUBLICA:8000" "$FRONTEND_DIR/.env"; then
        print_message "✅ URL de API configurada correctamente"
    else
        print_warning "⚠️ URL de API no configurada correctamente"
    fi
else
    print_error "❌ Archivo .env del frontend no existe"
fi

# ===========================================
# VERIFICAR SERVICIOS DE IA
# ===========================================
print_step "Verificando servicios de IA..."

if [ -d "$FRONTEND_DIR/src/services" ]; then
    SERVICES_COUNT=$(ls "$FRONTEND_DIR/src/services" | wc -l)
    print_message "✅ Servicios de IA encontrados: $SERVICES_COUNT servicios"
    
    # Verificar servicios específicos
    SERVICES=(
        "IAMejoradaService.js"
        "IAsProfesionalesService.js"
        "SistemaIAProfesionalService.js"
        "ChatGPTMejoradoService.js"
        "IAsTecnicasService.js"
        "ConsejoIAService.js"
        "AnalisisJuridicoService.js"
        "AnalisisNarrativoProfesionalService.js"
        "ConsejoVeeduriaTerritorialService.js"
        "api.js"
        "authService.js"
        "configuracion.js"
        "registroService.js"
    )
    
    for service in "${SERVICES[@]}"; do
        if [ -f "$FRONTEND_DIR/src/services/$service" ]; then
            print_message "✅ $service"
        else
            print_warning "⚠️ $service no encontrado"
        fi
    done
else
    print_error "❌ Directorio de servicios de IA no existe"
fi

# ===========================================
# VERIFICAR BASE DE DATOS
# ===========================================
print_step "Verificando base de datos..."

# Verificar conexión a MySQL
if mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT 1;" > /dev/null 2>&1; then
    print_message "✅ Conexión a MySQL exitosa"
    
    # Verificar tablas
    TABLES_COUNT=$(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SHOW TABLES;" | wc -l)
    print_message "✅ Tablas en la base de datos: $((TABLES_COUNT - 1))"
    
    # Verificar migraciones
    MIGRATIONS_COUNT=$(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT COUNT(*) FROM migrations;" 2>/dev/null | tail -n 1 || echo "0")
    print_message "✅ Migraciones ejecutadas: $MIGRATIONS_COUNT"
    
    # Verificar usuarios
    USERS_COUNT=$(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1 || echo "0")
    print_message "✅ Usuarios en la base de datos: $USERS_COUNT"
else
    print_error "❌ No se puede conectar a la base de datos"
fi

# ===========================================
# VERIFICAR PERMISOS
# ===========================================
print_step "Verificando permisos..."

# Verificar permisos del backend
if [ -d "$BACKEND_DIR" ]; then
    OWNER=$(stat -c '%U:%G' "$BACKEND_DIR")
    if [ "$OWNER" = "www-data:www-data" ]; then
        print_message "✅ Permisos del backend correctos"
    else
        print_warning "⚠️ Permisos del backend incorrectos: $OWNER"
    fi
else
    print_error "❌ Directorio del backend no existe"
fi

# Verificar permisos del frontend
if [ -d "$FRONTEND_DIR" ]; then
    OWNER=$(stat -c '%U:%G' "$FRONTEND_DIR")
    if [ "$OWNER" = "www-data:www-data" ]; then
        print_message "✅ Permisos del frontend correctos"
    else
        print_warning "⚠️ Permisos del frontend incorrectos: $OWNER"
    fi
else
    print_error "❌ Directorio del frontend no existe"
fi

# ===========================================
# VERIFICAR FIREWALL
# ===========================================
print_step "Verificando firewall..."

# Verificar estado del firewall
if ufw status | grep -q "Status: active"; then
    print_message "✅ Firewall activo"
    
    # Verificar reglas
    if ufw status | grep -q "3000"; then
        print_message "✅ Puerto 3000 permitido"
    else
        print_warning "⚠️ Puerto 3000 no permitido"
    fi
    
    if ufw status | grep -q "8000"; then
        print_message "✅ Puerto 8000 permitido"
    else
        print_warning "⚠️ Puerto 8000 no permitido"
    fi
else
    print_warning "⚠️ Firewall no activo"
fi

# ===========================================
# VERIFICAR RECURSOS DEL SISTEMA
# ===========================================
print_step "Verificando recursos del sistema..."

# Verificar memoria
MEMORY=$(free -h | grep "Mem:" | awk '{print $2}')
print_message "Memoria disponible: $MEMORY"

# Verificar espacio en disco
DISK=$(df -h / | awk 'NR==2 {print $4}')
print_message "Espacio disponible: $DISK"

# Verificar CPU
CPU=$(nproc)
print_message "Núcleos de CPU: $CPU"

# ===========================================
# VERIFICAR LOGS
# ===========================================
print_step "Verificando logs..."

# Verificar logs de PM2
if [ -f "/var/log/backend-csdt/combined.log" ]; then
    print_message "✅ Logs del backend disponibles"
else
    print_warning "⚠️ Logs del backend no disponibles"
fi

if [ -f "/var/log/frontend-csdt/combined.log" ]; then
    print_message "✅ Logs del frontend disponibles"
else
    print_warning "⚠️ Logs del frontend no disponibles"
fi

# ===========================================
# CREAR REPORTE DE VERIFICACIÓN
# ===========================================
print_step "Creando reporte de verificación..."

cat > /var/log/csdt_verificacion.log << EOF
=== REPORTE DE VERIFICACIÓN CSDT ===
Fecha: $(date)
Servidor: $IP_PUBLICA

=== SERVICIOS DEL SISTEMA ===
PHP: $(php --version | head -n1 | cut -d' ' -f2)
Composer: $(composer --version | cut -d' ' -f3)
Node.js: $(node --version | cut -d'v' -f2)
npm: $(npm --version)
PM2: $(pm2 --version)
MySQL: $(mysql --version | cut -d' ' -f3 | cut -d',' -f1)
Nginx: $(nginx -v 2>&1 | cut -d'/' -f2)

=== ESTADO DE PM2 ===
$(pm2 status)

=== CONECTIVIDAD ===
Backend local: $(curl -s http://localhost:8000 > /dev/null && echo "OK" || echo "ERROR")
Frontend local: $(curl -s http://localhost:3000 > /dev/null && echo "OK" || echo "ERROR")
Backend externo: $(curl -s http://$IP_PUBLICA:8000 > /dev/null && echo "OK" || echo "ERROR")
Frontend externo: $(curl -s http://$IP_PUBLICA:3000 > /dev/null && echo "OK" || echo "ERROR")

=== BASE DE DATOS ===
Conexión: $(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT 1;" > /dev/null 2>&1 && echo "OK" || echo "ERROR")
Tablas: $(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SHOW TABLES;" | wc -l)
Migraciones: $(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT COUNT(*) FROM migrations;" 2>/dev/null | tail -n 1 || echo "0")
Usuarios: $(mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT COUNT(*) FROM users;" 2>/dev/null | tail -n 1 || echo "0")

=== RECURSOS DEL SISTEMA ===
Memoria: $(free -h | grep "Mem:" | awk '{print $2}')
Disco: $(df -h / | awk 'NR==2 {print $4}')
CPU: $(nproc) núcleos

=== FIREWALL ===
$(ufw status)
EOF

print_message "✅ Reporte de verificación creado: /var/log/csdt_verificacion.log"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "VERIFICACIÓN COMPLETADA"

print_message "✅ Verificación del sistema completada"
print_message "✅ Reporte de verificación creado"
print_message "✅ Todos los servicios verificados"

print_warning "INFORMACIÓN DE ACCESO:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"
print_warning "Reporte: /var/log/csdt_verificacion.log"

print_warning "COMANDOS ÚTILES:"
print_warning "Ver estado: pm2 status"
print_warning "Ver logs: pm2 logs"
print_warning "Reiniciar: pm2 restart all"
print_warning "Monitoreo: /usr/local/bin/monitor_csdt.sh"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./10_solucionar_errores.sh (si hay problemas)"

print_message "¡Verificación completada correctamente!"
