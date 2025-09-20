#!/bin/bash

# ===========================================
# SCRIPT PARA CREAR ECOSYSTEM.CONFIG.JS DEL BACKEND
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

# Variables de configuración
BACKEND_DIR="/var/www/backend-csdt"
IP_PUBLICA="64.225.113.49"

print_header "CREANDO ECOSYSTEM.CONFIG.JS PARA BACKEND"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./crear_ecosystem_backend.sh)"
    exit 1
fi

# ===========================================
# NAVEGAR AL DIRECTORIO DEL BACKEND
# ===========================================
print_message "Navegando al directorio del backend..."

cd "$BACKEND_DIR"

if [ ! -f "artisan" ]; then
    print_error "❌ Archivo artisan no encontrado. ¿Está instalado el backend?"
    exit 1
fi

print_message "✅ Directorio del backend: $BACKEND_DIR"

# ===========================================
# CREAR CARPETA AYUDA
# ===========================================
print_message "Creando carpeta ayuda..."

mkdir -p ayuda
chown -R www-data:www-data ayuda
chmod -R 755 ayuda

print_message "✅ Carpeta ayuda creada"

# ===========================================
# CREAR ARCHIVO ECOSYSTEM.CONFIG.JS
# ===========================================
print_message "Creando archivo ecosystem.config.js..."

cat > ecosystem.config.js << 'EOF'
module.exports = {
    apps: [{
        name: 'backend-csdt',
        script: 'artisan',
        args: 'serve --host=0.0.0.0 --port=8000',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'development',
            APP_ENV: 'local'
        },
        env_production: {
            NODE_ENV: 'production',
            APP_ENV: 'production'
        },
        log_file: '/var/log/backend-csdt/combined.log',
        out_file: '/var/log/backend-csdt/out.log',
        error_file: '/var/log/backend-csdt/error.log',
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        merge_logs: true,
        max_memory_restart: '512M',
        watch: false,
        ignore_watch: ['node_modules', 'storage/logs', 'vendor', 'ayuda'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s',
        cwd: '/var/www/backend-csdt',
        env_file: '/var/www/backend-csdt/.env',
        error_file: '/var/log/backend-csdt/error.log',
        out_file: '/var/log/backend-csdt/out.log',
        log_file: '/var/log/backend-csdt/combined.log',
        time: true,
        autorestart: true,
        max_restarts: 10,
        min_uptime: '10s',
        kill_timeout: 5000,
        wait_ready: true,
        listen_timeout: 10000,
        kill_timeout: 5000
    }]
};
EOF

print_message "✅ Archivo ecosystem.config.js creado"

# ===========================================
# CREAR SCRIPT DE INICIO DEL BACKEND
# ===========================================
print_message "Creando script de inicio del backend..."

cat > ayuda/iniciar_backend.sh << 'EOF'
#!/bin/bash
# Script para iniciar el backend CSDT

echo "Iniciando backend CSDT..."

# Navegar al directorio del backend
cd /var/www/backend-csdt

# Verificar que existe el archivo artisan
if [ ! -f "artisan" ]; then
    echo "❌ Error: Archivo artisan no encontrado"
    exit 1
fi

# Verificar que existe el archivo .env
if [ ! -f ".env" ]; then
    echo "❌ Error: Archivo .env no encontrado"
    exit 1
fi

# Verificar que existe el archivo ecosystem.config.js
if [ ! -f "ecosystem.config.js" ]; then
    echo "❌ Error: Archivo ecosystem.config.js no encontrado"
    exit 1
fi

# Iniciar con PM2
pm2 start ecosystem.config.js --env production

echo "✅ Backend iniciado correctamente"
echo "Ver estado con: pm2 status"
echo "Ver logs con: pm2 logs backend-csdt"
EOF

chmod +x ayuda/iniciar_backend.sh

print_message "✅ Script de inicio del backend creado"

# ===========================================
# CREAR SCRIPT DE PARADA DEL BACKEND
# ===========================================
print_message "Creando script de parada del backend..."

cat > ayuda/detener_backend.sh << 'EOF'
#!/bin/bash
# Script para detener el backend CSDT

echo "Deteniendo backend CSDT..."

# Detener backend con PM2
pm2 stop backend-csdt

echo "✅ Backend detenido correctamente"
EOF

chmod +x ayuda/detener_backend.sh

print_message "✅ Script de parada del backend creado"

# ===========================================
# CREAR SCRIPT DE REINICIO DEL BACKEND
# ===========================================
print_message "Creando script de reinicio del backend..."

cat > ayuda/reiniciar_backend.sh << 'EOF'
#!/bin/bash
# Script para reiniciar el backend CSDT

echo "Reiniciando backend CSDT..."

# Reiniciar backend con PM2
pm2 restart backend-csdt

echo "✅ Backend reiniciado correctamente"
EOF

chmod +x ayuda/reiniciar_backend.sh

print_message "✅ Script de reinicio del backend creado"

# ===========================================
# CREAR SCRIPT DE VERIFICACIÓN DEL BACKEND
# ===========================================
print_message "Creando script de verificación del backend..."

cat > ayuda/verificar_backend.sh << 'EOF'
#!/bin/bash
# Script para verificar el estado del backend CSDT

echo "=== VERIFICACIÓN BACKEND CSDT ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de PM2
echo "=== ESTADO DE PM2 ==="
pm2 status
echo ""

# Verificar conectividad
echo "=== CONECTIVIDAD ==="
curl -s http://localhost:8000 > /dev/null && echo "✅ Backend local responde" || echo "❌ Backend local no responde"
curl -s http://64.225.113.49:8000 > /dev/null && echo "✅ Backend externo responde" || echo "❌ Backend externo no responde"
echo ""

# Verificar archivos importantes
echo "=== ARCHIVOS IMPORTANTES ==="
[ -f "/var/www/backend-csdt/artisan" ] && echo "✅ artisan" || echo "❌ artisan"
[ -f "/var/www/backend-csdt/.env" ] && echo "✅ .env" || echo "❌ .env"
[ -f "/var/www/backend-csdt/ecosystem.config.js" ] && echo "✅ ecosystem.config.js" || echo "❌ ecosystem.config.js"
echo ""

# Verificar logs
echo "=== LOGS RECIENTES ==="
if [ -f "/var/log/backend-csdt/combined.log" ]; then
    echo "Últimas 5 líneas del log:"
    tail -n 5 /var/log/backend-csdt/combined.log
else
    echo "❌ No hay logs disponibles"
fi
EOF

chmod +x ayuda/verificar_backend.sh

print_message "✅ Script de verificación del backend creado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

chown -R www-data:www-data "$BACKEND_DIR"
chmod -R 755 "$BACKEND_DIR"
chmod -R 755 "$BACKEND_DIR/ayuda"

print_message "✅ Permisos configurados"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_message "Verificando configuración..."

# Verificar que el archivo se creó correctamente
if [ -f "ecosystem.config.js" ]; then
    print_message "✅ Archivo ecosystem.config.js creado correctamente"
else
    print_error "❌ Error creando archivo ecosystem.config.js"
    exit 1
fi

# Verificar que la carpeta ayuda se creó
if [ -d "ayuda" ]; then
    print_message "✅ Carpeta ayuda creada correctamente"
    print_message "Scripts en ayuda:"
    ls -la ayuda/
else
    print_error "❌ Error creando carpeta ayuda"
    exit 1
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "ECOSYSTEM.CONFIG.JS DEL BACKEND CREADO EXITOSAMENTE"

print_message "✅ Archivo ecosystem.config.js creado"
print_message "✅ Carpeta ayuda creada"
print_message "✅ Scripts de gestión creados"
print_message "✅ Permisos configurados"

print_warning "INFORMACIÓN DEL BACKEND:"
print_warning "Directorio: $BACKEND_DIR"
print_warning "Archivo: ecosystem.config.js"
print_warning "Carpeta ayuda: $BACKEND_DIR/ayuda"
print_warning "Scripts disponibles:"
print_warning "  - ayuda/iniciar_backend.sh"
print_warning "  - ayuda/detener_backend.sh"
print_warning "  - ayuda/reiniciar_backend.sh"
print_warning "  - ayuda/verificar_backend.sh"

print_warning "COMANDOS ÚTILES:"
print_warning "Iniciar: ./ayuda/iniciar_backend.sh"
print_warning "Detener: ./ayuda/detener_backend.sh"
print_warning "Reiniciar: ./ayuda/reiniciar_backend.sh"
print_warning "Verificar: ./ayuda/verificar_backend.sh"

print_message "¡Ecosystem.config.js del backend creado correctamente!"
