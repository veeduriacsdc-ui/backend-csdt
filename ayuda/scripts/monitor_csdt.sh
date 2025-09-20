#!/bin/bash

# ===========================================
# SCRIPT DE MONITOREO CSDT
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

# Función para limpiar pantalla
clear_screen() {
    clear
    echo -e "${CYAN}===========================================${NC}"
    echo -e "${CYAN}    MONITOR CSDT - $(date)${NC}"
    echo -e "${CYAN}===========================================${NC}"
    echo ""
}

# Función para mostrar estado de servicios
show_services_status() {
    echo -e "${PURPLE}=== ESTADO DE SERVICIOS ===${NC}"
    pm2 status
    echo ""
}

# Función para mostrar conectividad
show_connectivity() {
    echo -e "${PURPLE}=== CONECTIVIDAD ===${NC}"
    
    # Backend local
    if curl -s http://localhost:8000 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Backend local: http://localhost:8000${NC}"
    else
        echo -e "${RED}❌ Backend local no responde${NC}"
    fi
    
    # Frontend local
    if curl -s http://localhost:3000 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Frontend local: http://localhost:3000${NC}"
    else
        echo -e "${RED}❌ Frontend local no responde${NC}"
    fi
    
    # Backend externo
    if curl -s http://64.225.113.49:8000 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Backend externo: http://64.225.113.49:8000${NC}"
    else
        echo -e "${RED}❌ Backend externo no accesible${NC}"
    fi
    
    # Frontend externo
    if curl -s http://64.225.113.49:3000 > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Frontend externo: http://64.225.113.49:3000${NC}"
    else
        echo -e "${RED}❌ Frontend externo no accesible${NC}"
    fi
    
    echo ""
}

# Función para mostrar recursos del sistema
show_system_resources() {
    echo -e "${PURPLE}=== RECURSOS DEL SISTEMA ===${NC}"
    
    # Memoria
    echo -e "${CYAN}Memoria:${NC}"
    free -h | grep -E "(Mem|Swap)"
    
    # CPU
    echo -e "${CYAN}CPU:${NC}"
    top -bn1 | grep "Cpu(s)" | head -1
    
    # Disco
    echo -e "${CYAN}Disco:${NC}"
    df -h / | tail -1
    
    # Uptime
    echo -e "${CYAN}Uptime:${NC}"
    uptime
    
    echo ""
}

# Función para mostrar logs recientes
show_recent_logs() {
    echo -e "${PURPLE}=== LOGS RECIENTES ===${NC}"
    
    # Logs del backend
    if [ -f "/var/log/backend-csdt/error.log" ]; then
        echo -e "${CYAN}Backend (últimas 3 líneas):${NC}"
        tail -3 /var/log/backend-csdt/error.log
    fi
    
    # Logs del frontend
    if [ -f "/var/log/frontend-csdt/error.log" ]; then
        echo -e "${CYAN}Frontend (últimas 3 líneas):${NC}"
        tail -3 /var/log/frontend-csdt/error.log
    fi
    
    echo ""
}

# Función para mostrar estadísticas de red
show_network_stats() {
    echo -e "${PURPLE}=== ESTADÍSTICAS DE RED ===${NC}"
    
    # Conexiones activas
    echo -e "${CYAN}Conexiones activas:${NC}"
    netstat -an | grep -E ":(3000|8000)" | wc -l
    
    # Puerto 3000
    echo -e "${CYAN}Puerto 3000 (Frontend):${NC}"
    netstat -an | grep ":3000" | head -3
    
    # Puerto 8000
    echo -e "${CYAN}Puerto 8000 (Backend):${NC}"
    netstat -an | grep ":8000" | head -3
    
    echo ""
}

# Función para mostrar estadísticas de base de datos
show_database_stats() {
    echo -e "${PURPLE}=== ESTADÍSTICAS DE BASE DE DATOS ===${NC}"
    
    cd /var/www/backend-csdt
    
    # Verificar conexión
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión exitosa';" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ Base de datos conectada${NC}"
        
        # Mostrar estadísticas básicas
        echo -e "${CYAN}Tablas principales:${NC}"
        php artisan tinker --execute="
        echo 'Usuarios: ' . DB::table('users')->count();
        echo 'Sesiones: ' . DB::table('sessions')->count();
        echo 'Migraciones: ' . DB::table('migrations')->count();
        "
    else
        echo -e "${RED}❌ Error de conexión a base de datos${NC}"
    fi
    
    echo ""
}

# Función para mostrar menú de opciones
show_menu() {
    echo -e "${PURPLE}=== OPCIONES ===${NC}"
    echo "1. Reiniciar backend"
    echo "2. Reiniciar frontend"
    echo "3. Reiniciar todos los servicios"
    echo "4. Ver logs completos"
    echo "5. Verificar sistema"
    echo "6. Limpiar cache"
    echo "7. Salir"
    echo ""
    echo -n "Selecciona una opción (1-7): "
}

# Función para manejar opciones del menú
handle_menu_option() {
    case $1 in
        1)
            echo "Reiniciando backend..."
            pm2 restart backend-csdt
            echo "Backend reiniciado"
            ;;
        2)
            echo "Reiniciando frontend..."
            pm2 restart frontend-csdt
            echo "Frontend reiniciado"
            ;;
        3)
            echo "Reiniciando todos los servicios..."
            pm2 restart all
            echo "Todos los servicios reiniciados"
            ;;
        4)
            echo "Mostrando logs completos..."
            pm2 logs --lines 50
            ;;
        5)
            echo "Verificando sistema..."
            /var/www/backend-csdt/ayuda/scripts/verificar_csdt.sh
            ;;
        6)
            echo "Limpiando cache..."
            /var/www/backend-csdt/ayuda/scripts/limpiar_csdt.sh
            ;;
        7)
            echo "Saliendo del monitor..."
            exit 0
            ;;
        *)
            echo "Opción inválida"
            ;;
    esac
}

# Función principal de monitoreo
monitor_loop() {
    while true; do
        clear_screen
        show_services_status
        show_connectivity
        show_system_resources
        show_recent_logs
        show_network_stats
        show_database_stats
        
        echo -e "${YELLOW}Presiona Ctrl+C para salir o 'm' para menú${NC}"
        
        # Leer input con timeout
        read -t 5 -n 1 input
        
        if [ "$input" = "m" ]; then
            clear_screen
            show_menu
            read -n 1 option
            echo ""
            handle_menu_option $option
            echo "Presiona Enter para continuar..."
            read
        fi
    done
}

# Función para mostrar ayuda
show_help() {
    echo "Uso: $0 [opciones]"
    echo ""
    echo "Opciones:"
    echo "  -h, --help     Mostrar esta ayuda"
    echo "  -s, --status   Mostrar estado una vez y salir"
    echo "  -m, --monitor  Iniciar monitoreo continuo (por defecto)"
    echo ""
    echo "Ejemplos:"
    echo "  $0              # Iniciar monitoreo continuo"
    echo "  $0 --status     # Mostrar estado una vez"
    echo "  $0 --monitor    # Iniciar monitoreo continuo"
}

# Función para mostrar estado una vez
show_status_once() {
    clear_screen
    show_services_status
    show_connectivity
    show_system_resources
    show_recent_logs
    show_network_stats
    show_database_stats
}

# Manejar argumentos de línea de comandos
case "$1" in
    -h|--help)
        show_help
        exit 0
        ;;
    -s|--status)
        show_status_once
        exit 0
        ;;
    -m|--monitor|"")
        monitor_loop
        ;;
    *)
        echo "Opción desconocida: $1"
        show_help
        exit 1
        ;;
esac
