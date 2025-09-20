#!/bin/bash

# ===========================================
# SCRIPT DE VERIFICACIÓN COMPLETA DE DESPLIEGUE
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# UBUNTU/DIGITALOCEAN - VERIFICACIÓN COMPLETA
# ===========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
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

print_step() {
    echo -e "${PURPLE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_header "VERIFICACIÓN COMPLETA DE DESPLIEGUE CSDT"

# ===========================================
# VERIFICAR SISTEMA OPERATIVO
# ===========================================
print_step "Verificando sistema operativo..."

echo -e "${CYAN}=== INFORMACIÓN DEL SISTEMA ===${NC}"
echo "Sistema: $(uname -a)"
echo "Distribución: $(lsb_release -d 2>/dev/null || echo 'No disponible')"
echo "Arquitectura: $(uname -m)"
echo "Memoria: $(free -h | grep Mem | awk '{print $2}')"
echo "Disco: $(df -h / | tail -1 | awk '{print $4}') disponible"

# ===========================================
# VERIFICAR HERRAMIENTAS BÁSICAS
# ===========================================
print_step "Verificando herramientas básicas..."

echo -e "${CYAN}=== HERRAMIENTAS BÁSICAS ===${NC}"

# Verificar PHP
if command -v php >/dev/null 2>&1; then
    echo "✅ PHP: $(php --version | head -1)"
    php -m | grep -E "(mysql|pgsql|sqlite|curl|gd|mbstring|xml|zip|bcmath|intl|redis)" >/dev/null && echo "✅ Extensiones PHP críticas instaladas" || echo "❌ Extensiones PHP críticas faltantes"
else
    echo "❌ PHP no instalado"
fi

# Verificar Composer
if command -v composer >/dev/null 2>&1; then
    echo "✅ Composer: $(composer --version | head -1)"
else
    echo "❌ Composer no instalado"
fi

# Verificar Node.js
if command -v node >/dev/null 2>&1; then
    echo "✅ Node.js: $(node --version)"
else
    echo "❌ Node.js no instalado"
fi

# Verificar NPM
if command -v npm >/dev/null 2>&1; then
    echo "✅ NPM: $(npm --version)"
else
    echo "❌ NPM no instalado"
fi

# Verificar PM2
if command -v pm2 >/dev/null 2>&1; then
    echo "✅ PM2: $(pm2 --version)"
else
    echo "❌ PM2 no instalado"
fi

# ===========================================
# VERIFICAR SERVICIOS
# ===========================================
print_step "Verificando servicios del sistema..."

echo -e "${CYAN}=== SERVICIOS DEL SISTEMA ===${NC}"

# Verificar MySQL
if systemctl is-active --quiet mysql; then
    echo "✅ MySQL: Activo"
    mysql -u csdt -p123 -e "SELECT 1;" >/dev/null 2>&1 && echo "✅ MySQL: Conexión exitosa" || echo "❌ MySQL: Error de conexión"
else
    echo "❌ MySQL: Inactivo"
fi

# Verificar Redis
if systemctl is-active --quiet redis-server; then
    echo "✅ Redis: Activo"
    redis-cli ping >/dev/null 2>&1 && echo "✅ Redis: Conexión exitosa" || echo "❌ Redis: Error de conexión"
else
    echo "❌ Redis: Inactivo"
fi

# Verificar Nginx
if systemctl is-active --quiet nginx; then
    echo "✅ Nginx: Activo"
else
    echo "⚠️ Nginx: Inactivo (opcional)"
fi

# Verificar Fail2ban
if systemctl is-active --quiet fail2ban; then
    echo "✅ Fail2ban: Activo"
else
    echo "⚠️ Fail2ban: Inactivo"
fi

# ===========================================
# VERIFICAR PROYECTO BACKEND
# ===========================================
print_step "Verificando proyecto backend..."

echo -e "${CYAN}=== PROYECTO BACKEND ===${NC}"

if [ -d "/var/www/backend-csdt" ]; then
    echo "✅ Directorio backend existe"
    
    # Verificar archivos críticos
    [ -f "/var/www/backend-csdt/artisan" ] && echo "✅ artisan existe" || echo "❌ artisan no encontrado"
    [ -f "/var/www/backend-csdt/composer.json" ] && echo "✅ composer.json existe" || echo "❌ composer.json no encontrado"
    [ -f "/var/www/backend-csdt/ecosystem.config.js" ] && echo "✅ ecosystem.config.js existe" || echo "❌ ecosystem.config.js no encontrado"
    [ -f "/var/www/backend-csdt/.env" ] && echo "✅ .env existe" || echo "❌ .env no encontrado"
    
    # Verificar dependencias
    if [ -d "/var/www/backend-csdt/vendor" ]; then
        echo "✅ Dependencias PHP instaladas"
        composer show --installed | grep -E "(openai|anthropic|guzzle)" >/dev/null && echo "✅ Dependencias de IA instaladas" || echo "❌ Dependencias de IA faltantes"
    else
        echo "❌ Dependencias PHP no instaladas"
    fi
    
    if [ -d "/var/www/backend-csdt/node_modules" ]; then
        echo "✅ Dependencias Node.js instaladas"
    else
        echo "❌ Dependencias Node.js no instaladas"
    fi
else
    echo "❌ Directorio backend no existe"
fi

# ===========================================
# VERIFICAR PROYECTO FRONTEND
# ===========================================
print_step "Verificando proyecto frontend..."

echo -e "${CYAN}=== PROYECTO FRONTEND ===${NC}"

if [ -d "/var/www/frontend-csdt" ]; then
    echo "✅ Directorio frontend existe"
    
    # Verificar archivos críticos
    [ -f "/var/www/frontend-csdt/package.json" ] && echo "✅ package.json existe" || echo "❌ package.json no encontrado"
    [ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ] && echo "✅ ecosystem-frontend.config.js existe" || echo "❌ ecosystem-frontend.config.js no encontrado"
    
    # Verificar dependencias
    if [ -d "/var/www/frontend-csdt/node_modules" ]; then
        echo "✅ Dependencias instaladas"
        npm list --depth=0 | grep -E "(react|axios|leaflet)" >/dev/null && echo "✅ Dependencias críticas instaladas" || echo "❌ Dependencias críticas faltantes"
    else
        echo "❌ Dependencias no instaladas"
    fi
else
    echo "❌ Directorio frontend no existe"
fi

# ===========================================
# VERIFICAR CONECTIVIDAD
# ===========================================
print_step "Verificando conectividad..."

echo -e "${CYAN}=== CONECTIVIDAD ===${NC}"

# Verificar backend
if curl -s http://localhost:8000 >/dev/null 2>&1; then
    echo "✅ Backend: http://localhost:8000 respondiendo"
else
    echo "❌ Backend: http://localhost:8000 no responde"
fi

# Verificar frontend
if curl -s http://localhost:3000 >/dev/null 2>&1; then
    echo "✅ Frontend: http://localhost:3000 respondiendo"
else
    echo "❌ Frontend: http://localhost:3000 no responde"
fi

# Verificar conectividad externa
if curl -s http://64.225.113.49:8000 >/dev/null 2>&1; then
    echo "✅ Backend externo: http://64.225.113.49:8000 respondiendo"
else
    echo "⚠️ Backend externo: http://64.225.113.49:8000 no accesible"
fi

if curl -s http://64.225.113.49:3000 >/dev/null 2>&1; then
    echo "✅ Frontend externo: http://64.225.113.49:3000 respondiendo"
else
    echo "⚠️ Frontend externo: http://64.225.113.49:3000 no accesible"
fi

# ===========================================
# VERIFICAR BASE DE DATOS
# ===========================================
print_step "Verificando base de datos..."

echo -e "${CYAN}=== BASE DE DATOS ===${NC}"

if mysql -u csdt -p123 -e "SELECT 1;" >/dev/null 2>&1; then
    echo "✅ Conexión a base de datos exitosa"
    
    # Verificar tablas
    TABLES=$(mysql -u csdt -p123 csdt_final -e "SHOW TABLES;" 2>/dev/null | wc -l)
    echo "✅ Número de tablas: $TABLES"
    
    # Verificar usuarios
    USERS=$(mysql -u csdt -p123 csdt_final -e "SELECT COUNT(*) FROM users;" 2>/dev/null | tail -1)
    echo "✅ Número de usuarios: $USERS"
else
    echo "❌ Error de conexión a base de datos"
fi

# ===========================================
# VERIFICAR PM2
# ===========================================
print_step "Verificando PM2..."

echo -e "${CYAN}=== PM2 ===${NC}"

if command -v pm2 >/dev/null 2>&1; then
    echo "✅ PM2 instalado"
    pm2 status
else
    echo "❌ PM2 no instalado"
fi

# ===========================================
# VERIFICAR LOGS
# ===========================================
print_step "Verificando logs..."

echo -e "${CYAN}=== LOGS ===${NC}"

if [ -d "/var/log/backend-csdt" ]; then
    echo "✅ Directorio de logs backend existe"
    [ -f "/var/log/backend-csdt/combined.log" ] && echo "✅ Log combinado backend existe" || echo "❌ Log combinado backend no existe"
else
    echo "❌ Directorio de logs backend no existe"
fi

if [ -d "/var/log/frontend-csdt" ]; then
    echo "✅ Directorio de logs frontend existe"
    [ -f "/var/log/frontend-csdt/combined.log" ] && echo "✅ Log combinado frontend existe" || echo "❌ Log combinado frontend no existe"
else
    echo "❌ Directorio de logs frontend no existe"
fi

# ===========================================
# VERIFICAR SERVICIOS DE IA
# ===========================================
print_step "Verificando servicios de IA..."

echo -e "${CYAN}=== SERVICIOS DE IA ===${NC}"

if [ -f "/var/www/backend-csdt/config/ia_services.php" ]; then
    echo "✅ Configuración de IA existe"
else
    echo "❌ Configuración de IA no encontrada"
fi

# Verificar variables de entorno de IA
if grep -q "OPENAI_API_KEY" /var/www/backend-csdt/.env 2>/dev/null; then
    echo "✅ Variables de IA configuradas"
else
    echo "⚠️ Variables de IA no configuradas"
fi

# ===========================================
# VERIFICAR SEGURIDAD
# ===========================================
print_step "Verificando seguridad..."

echo -e "${CYAN}=== SEGURIDAD ===${NC}"

# Verificar UFW
if ufw status | grep -q "Status: active"; then
    echo "✅ UFW activo"
else
    echo "⚠️ UFW inactivo"
fi

# Verificar Fail2ban
if systemctl is-active --quiet fail2ban; then
    echo "✅ Fail2ban activo"
    fail2ban-client status | grep -q "sshd" && echo "✅ Fail2ban SSH configurado" || echo "⚠️ Fail2ban SSH no configurado"
else
    echo "⚠️ Fail2ban inactivo"
fi

# Verificar permisos
if [ -d "/var/www" ]; then
    PERMISOS=$(stat -c "%a" /var/www 2>/dev/null || echo "000")
    if [ "$PERMISOS" = "755" ]; then
        echo "✅ Permisos de /var/www correctos"
    else
        echo "⚠️ Permisos de /var/www: $PERMISOS"
    fi
fi

# ===========================================
# VERIFICAR RENDIMIENTO
# ===========================================
print_step "Verificando rendimiento..."

echo -e "${CYAN}=== RENDIMIENTO ===${NC}"

# Verificar memoria
MEMORIA_USADA=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
echo "✅ Uso de memoria: ${MEMORIA_USADA}%"

# Verificar CPU
CPU_USO=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
echo "✅ Uso de CPU: ${CPU_USO}%"

# Verificar espacio en disco
DISCO_USADO=$(df / | tail -1 | awk '{print $5}' | cut -d'%' -f1)
echo "✅ Uso de disco: ${DISCO_USADO}%"

# ===========================================
# RESUMEN FINAL
# ===========================================
print_header "RESUMEN DE VERIFICACIÓN"

echo -e "${CYAN}=== ESTADO GENERAL ===${NC}"

# Contar errores y advertencias
ERRORES=$(grep -c "❌" <<< "$(cat /dev/stdin)" 2>/dev/null || echo "0")
ADVERTENCIAS=$(grep -c "⚠️" <<< "$(cat /dev/stdin)" 2>/dev/null || echo "0")
EXITOS=$(grep -c "✅" <<< "$(cat /dev/stdin)" 2>/dev/null || echo "0")

echo "✅ Exitosos: $EXITOS"
echo "⚠️ Advertencias: $ADVERTENCIAS"
echo "❌ Errores: $ERRORES"

if [ "$ERRORES" -eq 0 ]; then
    print_success "🎉 Sistema CSDT completamente funcional"
    echo -e "${GREEN}El sistema está listo para producción${NC}"
else
    print_warning "⚠️ Sistema CSDT con problemas menores"
    echo -e "${YELLOW}Revisar los errores mostrados arriba${NC}"
fi

echo -e "${CYAN}=== INFORMACIÓN DE ACCESO ===${NC}"
echo "Backend local: http://localhost:8000"
echo "Frontend local: http://localhost:3000"
echo "Backend externo: http://64.225.113.49:8000"
echo "Frontend externo: http://64.225.113.49:3000"

echo -e "${CYAN}=== COMANDOS ÚTILES ===${NC}"
echo "Gestión: gestionar_csdt.sh"
echo "Inicio: iniciar_csdt.sh"
echo "Parada: detener_csdt.sh"
echo "Verificación: verificar_csdt.sh"
echo "Monitoreo IA: monitor_ia.sh"

print_header "VERIFICACIÓN COMPLETADA"
