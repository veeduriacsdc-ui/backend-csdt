#!/bin/bash

# ===========================================
# SCRIPT DE DIAGNÓSTICO COMPLETO CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

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

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Función para verificar servicios PM2
check_pm2_services() {
    print_header "VERIFICANDO SERVICIOS PM2"
    
    echo "Estado de PM2:"
    pm2 status
    
    echo ""
    echo "Información detallada:"
    pm2 show backend-csdt
    pm2 show frontend-csdt
    
    echo ""
    echo "Uso de memoria:"
    pm2 monit --no-interaction
}

# Función para verificar conectividad
check_connectivity() {
    print_header "VERIFICANDO CONECTIVIDAD"
    
    # Backend local
    echo "Probando backend local..."
    if curl -s -I http://localhost:8000 | head -1 | grep -q "200\|301\|302"; then
        print_success "✅ Backend local responde correctamente"
    else
        print_error "❌ Backend local no responde"
    fi
    
    # Frontend local
    echo "Probando frontend local..."
    if curl -s -I http://localhost:3000 | head -1 | grep -q "200\|301\|302"; then
        print_success "✅ Frontend local responde correctamente"
    else
        print_error "❌ Frontend local no responde"
    fi
    
    # Backend externo
    echo "Probando backend externo..."
    if curl -s -I http://64.225.113.49:8000 | head -1 | grep -q "200\|301\|302"; then
        print_success "✅ Backend externo responde correctamente"
    else
        print_error "❌ Backend externo no responde"
    fi
    
    # Frontend externo
    echo "Probando frontend externo..."
    if curl -s -I http://64.225.113.49:3000 | head -1 | grep -q "200\|301\|302"; then
        print_success "✅ Frontend externo responde correctamente"
    else
        print_error "❌ Frontend externo no responde"
    fi
}

# Función para verificar base de datos
check_database() {
    print_header "VERIFICANDO BASE DE DATOS"
    
    cd /var/www/backend-csdt
    
    # Verificar conexión
    echo "Probando conexión a base de datos..."
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión exitosa';" > /dev/null 2>&1; then
        print_success "✅ Conexión a base de datos exitosa"
        
        # Verificar tablas principales
        echo "Verificando tablas principales..."
        php artisan tinker --execute="
        echo 'Usuarios: ' . DB::table('users')->count();
        echo 'Sesiones: ' . DB::table('sessions')->count();
        echo 'Migraciones: ' . DB::table('migrations')->count();
        echo 'Tokens: ' . DB::table('personal_access_tokens')->count();
        "
        
        # Verificar migraciones
        echo "Verificando migraciones..."
        php artisan migrate:status
        
    else
        print_error "❌ Error de conexión a base de datos"
    fi
}

# Función para verificar archivos importantes
check_important_files() {
    print_header "VERIFICANDO ARCHIVOS IMPORTANTES"
    
    # Backend
    echo "Verificando archivos del backend..."
    
    if [ -f "/var/www/backend-csdt/.env" ]; then
        print_success "✅ .env del backend"
    else
        print_error "❌ .env del backend no encontrado"
    fi
    
    if [ -f "/var/www/backend-csdt/ecosystem.config.js" ]; then
        print_success "✅ ecosystem.config.js del backend"
    else
        print_error "❌ ecosystem.config.js del backend no encontrado"
    fi
    
    if [ -f "/var/www/backend-csdt/artisan" ]; then
        print_success "✅ artisan del backend"
    else
        print_error "❌ artisan del backend no encontrado"
    fi
    
    # Frontend
    echo "Verificando archivos del frontend..."
    
    if [ -f "/var/www/frontend-csdt/.env" ]; then
        print_success "✅ .env del frontend"
    else
        print_error "❌ .env del frontend no encontrado"
    fi
    
    if [ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ]; then
        print_success "✅ ecosystem-frontend.config.js del frontend"
    else
        print_error "❌ ecosystem-frontend.config.js del frontend no encontrado"
    fi
    
    if [ -d "/var/www/frontend-csdt/dist" ]; then
        print_success "✅ directorio dist del frontend"
    else
        print_error "❌ directorio dist del frontend no encontrado"
    fi
}

# Función para verificar permisos
check_permissions() {
    print_header "VERIFICANDO PERMISOS"
    
    # Backend
    echo "Verificando permisos del backend..."
    
    if [ -w "/var/www/backend-csdt/storage" ]; then
        print_success "✅ Permisos de escritura en storage del backend"
    else
        print_error "❌ Sin permisos de escritura en storage del backend"
    fi
    
    if [ -w "/var/www/backend-csdt/bootstrap/cache" ]; then
        print_success "✅ Permisos de escritura en bootstrap/cache del backend"
    else
        print_error "❌ Sin permisos de escritura en bootstrap/cache del backend"
    fi
    
    # Frontend
    echo "Verificando permisos del frontend..."
    
    if [ -w "/var/www/frontend-csdt/dist" ]; then
        print_success "✅ Permisos de escritura en dist del frontend"
    else
        print_error "❌ Sin permisos de escritura en dist del frontend"
    fi
    
    # Verificar propietarios
    echo "Verificando propietarios..."
    ls -la /var/www/backend-csdt | head -5
    ls -la /var/www/frontend-csdt | head -5
}

# Función para verificar logs
check_logs() {
    print_header "VERIFICANDO LOGS"
    
    # Backend
    echo "Verificando logs del backend..."
    
    if [ -f "/var/log/backend-csdt/error.log" ]; then
        print_success "✅ Log de errores del backend disponible"
        echo "Últimas 10 líneas del log de errores del backend:"
        tail -10 /var/log/backend-csdt/error.log
    else
        print_warning "⚠️ Log de errores del backend no encontrado"
    fi
    
    if [ -f "/var/log/backend-csdt/out.log" ]; then
        print_success "✅ Log de salida del backend disponible"
        echo "Últimas 5 líneas del log de salida del backend:"
        tail -5 /var/log/backend-csdt/out.log
    else
        print_warning "⚠️ Log de salida del backend no encontrado"
    fi
    
    # Frontend
    echo "Verificando logs del frontend..."
    
    if [ -f "/var/log/frontend-csdt/error.log" ]; then
        print_success "✅ Log de errores del frontend disponible"
        echo "Últimas 10 líneas del log de errores del frontend:"
        tail -10 /var/log/frontend-csdt/error.log
    else
        print_warning "⚠️ Log de errores del frontend no encontrado"
    fi
    
    if [ -f "/var/log/frontend-csdt/out.log" ]; then
        print_success "✅ Log de salida del frontend disponible"
        echo "Últimas 5 líneas del log de salida del frontend:"
        tail -5 /var/log/frontend-csdt/out.log
    else
        print_warning "⚠️ Log de salida del frontend no encontrado"
    fi
}

# Función para verificar recursos del sistema
check_system_resources() {
    print_header "VERIFICANDO RECURSOS DEL SISTEMA"
    
    # Memoria
    echo "Memoria disponible:"
    free -h
    
    # CPU
    echo "Uso de CPU:"
    top -bn1 | grep "Cpu(s)"
    
    # Disco
    echo "Espacio en disco:"
    df -h /
    
    # Uptime
    echo "Tiempo de actividad:"
    uptime
    
    # Procesos
    echo "Procesos relacionados con CSDT:"
    ps aux | grep -E "(php|node|pm2)" | grep -v grep
}

# Función para verificar red
check_network() {
    print_header "VERIFICANDO RED"
    
    # Puertos abiertos
    echo "Puertos abiertos:"
    netstat -tlnp | grep -E ":(3000|8000)"
    
    # Conexiones activas
    echo "Conexiones activas:"
    netstat -an | grep -E ":(3000|8000)" | wc -l
    
    # Firewall
    echo "Estado del firewall:"
    ufw status
    
    # Interfaces de red
    echo "Interfaces de red:"
    ip addr show
}

# Función para verificar dependencias
check_dependencies() {
    print_header "VERIFICANDO DEPENDENCIAS"
    
    # PHP
    echo "Versión de PHP:"
    php --version
    
    # Composer
    echo "Versión de Composer:"
    composer --version
    
    # Node.js
    echo "Versión de Node.js:"
    node --version
    
    # NPM
    echo "Versión de NPM:"
    npm --version
    
    # PM2
    echo "Versión de PM2:"
    pm2 --version
    
    # MySQL
    echo "Versión de MySQL:"
    mysql --version
}

# Función para verificar configuración
check_configuration() {
    print_header "VERIFICANDO CONFIGURACIÓN"
    
    # Backend
    echo "Configuración del backend:"
    cd /var/www/backend-csdt
    php artisan config:show | head -20
    
    # Frontend
    echo "Configuración del frontend:"
    cd /var/www/frontend-csdt
    cat .env | head -10
}

# Función para generar reporte
generate_report() {
    print_header "GENERANDO REPORTE"
    
    REPORT_FILE="/var/log/csdt_diagnostico_$(date +%Y%m%d_%H%M%S).txt"
    
    {
        echo "REPORTE DE DIAGNÓSTICO CSDT - $(date)"
        echo "=========================================="
        echo ""
        
        echo "=== SERVICIOS PM2 ==="
        pm2 status
        echo ""
        
        echo "=== CONECTIVIDAD ==="
        curl -I http://localhost:8000 2>/dev/null || echo "Backend local no responde"
        curl -I http://localhost:3000 2>/dev/null || echo "Frontend local no responde"
        curl -I http://64.225.113.49:8000 2>/dev/null || echo "Backend externo no responde"
        curl -I http://64.225.113.49:3000 2>/dev/null || echo "Frontend externo no responde"
        echo ""
        
        echo "=== RECURSOS DEL SISTEMA ==="
        free -h
        df -h /
        uptime
        echo ""
        
        echo "=== LOGS RECIENTES ==="
        echo "Backend (últimas 10 líneas):"
        tail -10 /var/log/backend-csdt/error.log 2>/dev/null || echo "Log no encontrado"
        echo ""
        echo "Frontend (últimas 10 líneas):"
        tail -10 /var/log/frontend-csdt/error.log 2>/dev/null || echo "Log no encontrado"
        echo ""
        
    } > "$REPORT_FILE"
    
    print_success "✅ Reporte generado: $REPORT_FILE"
}

# Función principal
main() {
    print_header "DIAGNÓSTICO COMPLETO CSDT"
    
    check_pm2_services
    check_connectivity
    check_database
    check_important_files
    check_permissions
    check_logs
    check_system_resources
    check_network
    check_dependencies
    check_configuration
    generate_report
    
    print_header "DIAGNÓSTICO COMPLETADO"
    
    print_message "Para más detalles, revisa el reporte generado"
    print_message "Para monitoreo en tiempo real, ejecuta: ./monitor_csdt.sh"
    print_message "Para reparar problemas, ejecuta: ./reparar_csdt.sh"
}

# Ejecutar función principal
main
