#!/bin/bash

# ===========================================
# SCRIPT PRINCIPAL PARA CREAR ECOSYSTEM.CONFIG.JS
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e  # Salir si hay algún error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
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
    echo -e "${PURPLE}[PASO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_header "CREANDO ARCHIVOS ECOSYSTEM.CONFIG.JS COMPLETOS"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./crear_ecosystem_completo.sh)"
    exit 1
fi

# ===========================================
# VERIFICAR SCRIPTS NECESARIOS
# ===========================================
print_step "Verificando scripts necesarios..."

SCRIPTS=(
    "crear_ecosystem_backend.sh"
    "crear_ecosystem_frontend.sh"
)

for script in "${SCRIPTS[@]}"; do
    if [ -f "scripts/$script" ]; then
        print_message "✅ $script encontrado"
    else
        print_error "❌ $script no encontrado"
        exit 1
    fi
done

print_success "Todos los scripts necesarios encontrados"

# ===========================================
# HACER SCRIPTS EJECUTABLES
# ===========================================
print_step "Haciendo scripts ejecutables..."

for script in "${SCRIPTS[@]}"; do
    chmod +x "scripts/$script"
done

print_success "Scripts hechos ejecutables"

# ===========================================
# CREAR ECOSYSTEM.CONFIG.JS DEL BACKEND
# ===========================================
print_step "Creando ecosystem.config.js del backend..."

if ./scripts/crear_ecosystem_backend.sh; then
    print_success "✅ Ecosystem.config.js del backend creado correctamente"
else
    print_error "❌ Error creando ecosystem.config.js del backend"
    exit 1
fi

# ===========================================
# CREAR ECOSYSTEM-FRONTEND.CONFIG.JS DEL FRONTEND
# ===========================================
print_step "Creando ecosystem-frontend.config.js del frontend..."

if ./scripts/crear_ecosystem_frontend.sh; then
    print_success "✅ Ecosystem-frontend.config.js del frontend creado correctamente"
else
    print_error "❌ Error creando ecosystem-frontend.config.js del frontend"
    exit 1
fi

# ===========================================
# CREAR SCRIPT DE GESTIÓN COMPLETA
# ===========================================
print_step "Creando script de gestión completa..."

cat > /usr/local/bin/gestionar_csdt.sh << 'EOF'
#!/bin/bash
# Script de gestión completa para CSDT

echo "=== GESTIÓN CSDT ==="
echo "Fecha: $(date)"
echo ""

# Función para mostrar menú
mostrar_menu() {
    echo "Selecciona una opción:"
    echo "1. Iniciar todos los servicios"
    echo "2. Detener todos los servicios"
    echo "3. Reiniciar todos los servicios"
    echo "4. Ver estado de servicios"
    echo "5. Ver logs"
    echo "6. Verificar instalación"
    echo "7. Compilar frontend"
    echo "8. Desarrollo frontend"
    echo "9. Salir"
    echo ""
    read -p "Opción: " opcion
}

# Función para iniciar servicios
iniciar_servicios() {
    echo "Iniciando servicios CSDT..."
    
    # Iniciar backend
    cd /var/www/backend-csdt
    ./ayuda/iniciar_backend.sh
    
    # Iniciar frontend
    cd /var/www/frontend-csdt
    ./ayuda/iniciar_frontend.sh
    
    echo "✅ Servicios iniciados"
}

# Función para detener servicios
detener_servicios() {
    echo "Deteniendo servicios CSDT..."
    
    # Detener backend
    cd /var/www/backend-csdt
    ./ayuda/detener_backend.sh
    
    # Detener frontend
    cd /var/www/frontend-csdt
    ./ayuda/detener_frontend.sh
    
    echo "✅ Servicios detenidos"
}

# Función para reiniciar servicios
reiniciar_servicios() {
    echo "Reiniciando servicios CSDT..."
    
    # Reiniciar backend
    cd /var/www/backend-csdt
    ./ayuda/reiniciar_backend.sh
    
    # Reiniciar frontend
    cd /var/www/frontend-csdt
    ./ayuda/reiniciar_frontend.sh
    
    echo "✅ Servicios reiniciados"
}

# Función para ver estado
ver_estado() {
    echo "=== ESTADO DE SERVICIOS ==="
    pm2 status
    echo ""
    echo "=== CONECTIVIDAD ==="
    curl -s http://localhost:8000 > /dev/null && echo "✅ Backend local" || echo "❌ Backend local"
    curl -s http://localhost:3000 > /dev/null && echo "✅ Frontend local" || echo "❌ Frontend local"
    curl -s http://64.225.113.49:8000 > /dev/null && echo "✅ Backend externo" || echo "❌ Backend externo"
    curl -s http://64.225.113.49:3000 > /dev/null && echo "✅ Frontend externo" || echo "❌ Frontend externo"
}

# Función para ver logs
ver_logs() {
    echo "=== LOGS DE SERVICIOS ==="
    pm2 logs --lines 20
}

# Función para verificar instalación
verificar_instalacion() {
    echo "=== VERIFICACIÓN BACKEND ==="
    cd /var/www/backend-csdt
    ./ayuda/verificar_backend.sh
    
    echo ""
    echo "=== VERIFICACIÓN FRONTEND ==="
    cd /var/www/frontend-csdt
    ./ayuda/verificar_frontend.sh
}

# Función para compilar frontend
compilar_frontend() {
    echo "Compilando frontend..."
    cd /var/www/frontend-csdt
    ./ayuda/compilar_frontend.sh
}

# Función para desarrollo frontend
desarrollo_frontend() {
    echo "Iniciando modo desarrollo..."
    cd /var/www/frontend-csdt
    ./ayuda/desarrollo_frontend.sh
}

# Bucle principal
while true; do
    mostrar_menu
    
    case $opcion in
        1) iniciar_servicios ;;
        2) detener_servicios ;;
        3) reiniciar_servicios ;;
        4) ver_estado ;;
        5) ver_logs ;;
        6) verificar_instalacion ;;
        7) compilar_frontend ;;
        8) desarrollo_frontend ;;
        9) echo "Saliendo..."; exit 0 ;;
        *) echo "Opción inválida" ;;
    esac
    
    echo ""
    read -p "Presiona Enter para continuar..."
    echo ""
done
EOF

chmod +x /usr/local/bin/gestionar_csdt.sh

print_success "✅ Script de gestión completa creado"

# ===========================================
# CREAR SCRIPT DE INICIO RÁPIDO
# ===========================================
print_step "Creando script de inicio rápido..."

cat > /usr/local/bin/iniciar_csdt.sh << 'EOF'
#!/bin/bash
# Script de inicio rápido para CSDT

echo "Iniciando CSDT rápidamente..."

# Iniciar backend
cd /var/www/backend-csdt
./ayuda/iniciar_backend.sh

# Iniciar frontend
cd /var/www/frontend-csdt
./ayuda/iniciar_frontend.sh

echo "✅ CSDT iniciado correctamente"
echo "Backend: http://64.225.113.49:8000"
echo "Frontend: http://64.225.113.49:3000"
EOF

chmod +x /usr/local/bin/iniciar_csdt.sh

print_success "✅ Script de inicio rápido creado"

# ===========================================
# CREAR SCRIPT DE PARADA RÁPIDA
# ===========================================
print_step "Creando script de parada rápida..."

cat > /usr/local/bin/detener_csdt.sh << 'EOF'
#!/bin/bash
# Script de parada rápida para CSDT

echo "Deteniendo CSDT..."

# Detener backend
cd /var/www/backend-csdt
./ayuda/detener_backend.sh

# Detener frontend
cd /var/www/frontend-csdt
./ayuda/detener_frontend.sh

echo "✅ CSDT detenido correctamente"
EOF

chmod +x /usr/local/bin/detener_csdt.sh

print_success "✅ Script de parada rápida creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN FINAL
# ===========================================
print_step "Verificando configuración final..."

# Verificar archivos del backend
if [ -f "/var/www/backend-csdt/ecosystem.config.js" ]; then
    print_message "✅ ecosystem.config.js del backend creado"
else
    print_error "❌ Error: ecosystem.config.js del backend no encontrado"
fi

if [ -d "/var/www/backend-csdt/ayuda" ]; then
    print_message "✅ Carpeta ayuda del backend creada"
    print_message "Scripts del backend: $(ls /var/www/backend-csdt/ayuda/ | wc -l)"
else
    print_error "❌ Error: Carpeta ayuda del backend no encontrada"
fi

# Verificar archivos del frontend
if [ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ]; then
    print_message "✅ ecosystem-frontend.config.js del frontend creado"
else
    print_error "❌ Error: ecosystem-frontend.config.js del frontend no encontrado"
fi

if [ -d "/var/www/frontend-csdt/ayuda" ]; then
    print_message "✅ Carpeta ayuda del frontend creada"
    print_message "Scripts del frontend: $(ls /var/www/frontend-csdt/ayuda/ | wc -l)"
else
    print_error "❌ Error: Carpeta ayuda del frontend no encontrada"
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "ARCHIVOS ECOSYSTEM.CONFIG.JS CREADOS EXITOSAMENTE"

print_success "✅ Ecosystem.config.js del backend creado"
print_success "✅ Ecosystem-frontend.config.js del frontend creado"
print_success "✅ Carpetas ayuda creadas en ambos proyectos"
print_success "✅ Scripts de gestión creados"
print_success "✅ Scripts de inicio y parada rápidos creados"

print_warning "INFORMACIÓN DE ARCHIVOS CREADOS:"
print_warning "Backend:"
print_warning "  - /var/www/backend-csdt/ecosystem.config.js"
print_warning "  - /var/www/backend-csdt/ayuda/ (scripts de gestión)"
print_warning "Frontend:"
print_warning "  - /var/www/frontend-csdt/ecosystem-frontend.config.js"
print_warning "  - /var/www/frontend-csdt/ayuda/ (scripts de gestión)"

print_warning "COMANDOS ÚTILES:"
print_warning "Gestión completa: /usr/local/bin/gestionar_csdt.sh"
print_warning "Inicio rápido: /usr/local/bin/iniciar_csdt.sh"
print_warning "Parada rápida: /usr/local/bin/detener_csdt.sh"

print_warning "SCRIPTS EN CARPETAS AYUDA:"
print_warning "Backend:"
print_warning "  - ./ayuda/iniciar_backend.sh"
print_warning "  - ./ayuda/detener_backend.sh"
print_warning "  - ./ayuda/reiniciar_backend.sh"
print_warning "  - ./ayuda/verificar_backend.sh"
print_warning "Frontend:"
print_warning "  - ./ayuda/iniciar_frontend.sh"
print_warning "  - ./ayuda/detener_frontend.sh"
print_warning "  - ./ayuda/reiniciar_frontend.sh"
print_warning "  - ./ayuda/compilar_frontend.sh"
print_warning "  - ./ayuda/verificar_frontend.sh"
print_warning "  - ./ayuda/desarrollo_frontend.sh"

print_message "¡Archivos ecosystem.config.js creados correctamente!"
print_message "Los archivos están configurados para funcionar dentro del proyecto en DigitalOcean"
