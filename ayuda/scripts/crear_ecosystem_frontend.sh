#!/bin/bash

# ===========================================
# SCRIPT PARA CREAR ECOSYSTEM-FRONTEND.CONFIG.JS DEL FRONTEND
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
FRONTEND_DIR="/var/www/frontend-csdt"
IP_PUBLICA="64.225.113.49"

print_header "CREANDO ECOSYSTEM-FRONTEND.CONFIG.JS PARA FRONTEND"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./crear_ecosystem_frontend.sh)"
    exit 1
fi

# ===========================================
# NAVEGAR AL DIRECTORIO DEL FRONTEND
# ===========================================
print_message "Navegando al directorio del frontend..."

cd "$FRONTEND_DIR"

if [ ! -f "package.json" ]; then
    print_error "❌ Archivo package.json no encontrado. ¿Está instalado el frontend?"
    exit 1
fi

print_message "✅ Directorio del frontend: $FRONTEND_DIR"

# ===========================================
# CREAR CARPETA AYUDA
# ===========================================
print_message "Creando carpeta ayuda..."

mkdir -p ayuda
chown -R www-data:www-data ayuda
chmod -R 755 ayuda

print_message "✅ Carpeta ayuda creada"

# ===========================================
# CREAR ARCHIVO ECOSYSTEM-FRONTEND.CONFIG.JS
# ===========================================
print_message "Creando archivo ecosystem-frontend.config.js..."

cat > ecosystem-frontend.config.js << 'EOF'
module.exports = {
    apps: [{
        name: 'frontend-csdt',
        script: 'npm',
        args: 'run preview -- --host 0.0.0.0 --port 3000',
        instances: 1,
        exec_mode: 'fork',
        env: {
            NODE_ENV: 'production'
        },
        log_file: '/var/log/frontend-csdt/combined.log',
        out_file: '/var/log/frontend-csdt/out.log',
        error_file: '/var/log/frontend-csdt/error.log',
        log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
        merge_logs: true,
        max_memory_restart: '512M',
        watch: false,
        ignore_watch: ['node_modules', 'dist', 'ayuda'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s',
        cwd: '/var/www/frontend-csdt',
        env_file: '/var/www/frontend-csdt/.env',
        error_file: '/var/log/frontend-csdt/error.log',
        out_file: '/var/log/frontend-csdt/out.log',
        log_file: '/var/log/frontend-csdt/combined.log',
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

print_message "✅ Archivo ecosystem-frontend.config.js creado"

# ===========================================
# CREAR SCRIPT DE INICIO DEL FRONTEND
# ===========================================
print_message "Creando script de inicio del frontend..."

cat > ayuda/iniciar_frontend.sh << 'EOF'
#!/bin/bash
# Script para iniciar el frontend CSDT

echo "Iniciando frontend CSDT..."

# Navegar al directorio del frontend
cd /var/www/frontend-csdt

# Verificar que existe el archivo package.json
if [ ! -f "package.json" ]; then
    echo "❌ Error: Archivo package.json no encontrado"
    exit 1
fi

# Verificar que existe el archivo .env
if [ ! -f ".env" ]; then
    echo "❌ Error: Archivo .env no encontrado"
    exit 1
fi

# Verificar que existe el archivo ecosystem-frontend.config.js
if [ ! -f "ecosystem-frontend.config.js" ]; then
    echo "❌ Error: Archivo ecosystem-frontend.config.js no encontrado"
    exit 1
fi

# Verificar que existe la carpeta dist
if [ ! -d "dist" ]; then
    echo "⚠️ Advertencia: Carpeta dist no encontrada, compilando..."
    npm run build
fi

# Iniciar con PM2
pm2 start ecosystem-frontend.config.js

echo "✅ Frontend iniciado correctamente"
echo "Ver estado con: pm2 status"
echo "Ver logs con: pm2 logs frontend-csdt"
EOF

chmod +x ayuda/iniciar_frontend.sh

print_message "✅ Script de inicio del frontend creado"

# ===========================================
# CREAR SCRIPT DE PARADA DEL FRONTEND
# ===========================================
print_message "Creando script de parada del frontend..."

cat > ayuda/detener_frontend.sh << 'EOF'
#!/bin/bash
# Script para detener el frontend CSDT

echo "Deteniendo frontend CSDT..."

# Detener frontend con PM2
pm2 stop frontend-csdt

echo "✅ Frontend detenido correctamente"
EOF

chmod +x ayuda/detener_frontend.sh

print_message "✅ Script de parada del frontend creado"

# ===========================================
# CREAR SCRIPT DE REINICIO DEL FRONTEND
# ===========================================
print_message "Creando script de reinicio del frontend..."

cat > ayuda/reiniciar_frontend.sh << 'EOF'
#!/bin/bash
# Script para reiniciar el frontend CSDT

echo "Reiniciando frontend CSDT..."

# Reiniciar frontend con PM2
pm2 restart frontend-csdt

echo "✅ Frontend reiniciado correctamente"
EOF

chmod +x ayuda/reiniciar_frontend.sh

print_message "✅ Script de reinicio del frontend creado"

# ===========================================
# CREAR SCRIPT DE COMPILACIÓN DEL FRONTEND
# ===========================================
print_message "Creando script de compilación del frontend..."

cat > ayuda/compilar_frontend.sh << 'EOF'
#!/bin/bash
# Script para compilar el frontend CSDT

echo "Compilando frontend CSDT..."

# Navegar al directorio del frontend
cd /var/www/frontend-csdt

# Verificar que existe el archivo package.json
if [ ! -f "package.json" ]; then
    echo "❌ Error: Archivo package.json no encontrado"
    exit 1
fi

# Instalar dependencias si es necesario
if [ ! -d "node_modules" ]; then
    echo "Instalando dependencias..."
    npm install
fi

# Compilar frontend
echo "Compilando para producción..."
npm run build

# Verificar que se compiló correctamente
if [ -d "dist" ]; then
    echo "✅ Frontend compilado correctamente"
    echo "Archivos en dist: $(ls dist/ | wc -l)"
else
    echo "❌ Error compilando frontend"
    exit 1
fi
EOF

chmod +x ayuda/compilar_frontend.sh

print_message "✅ Script de compilación del frontend creado"

# ===========================================
# CREAR SCRIPT DE VERIFICACIÓN DEL FRONTEND
# ===========================================
print_message "Creando script de verificación del frontend..."

cat > ayuda/verificar_frontend.sh << 'EOF'
#!/bin/bash
# Script para verificar el estado del frontend CSDT

echo "=== VERIFICACIÓN FRONTEND CSDT ==="
echo "Fecha: $(date)"
echo ""

# Verificar estado de PM2
echo "=== ESTADO DE PM2 ==="
pm2 status
echo ""

# Verificar conectividad
echo "=== CONECTIVIDAD ==="
curl -s http://localhost:3000 > /dev/null && echo "✅ Frontend local responde" || echo "❌ Frontend local no responde"
curl -s http://64.225.113.49:3000 > /dev/null && echo "✅ Frontend externo responde" || echo "❌ Frontend externo no responde"
echo ""

# Verificar archivos importantes
echo "=== ARCHIVOS IMPORTANTES ==="
[ -f "/var/www/frontend-csdt/package.json" ] && echo "✅ package.json" || echo "❌ package.json"
[ -f "/var/www/frontend-csdt/.env" ] && echo "✅ .env" || echo "❌ .env"
[ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ] && echo "✅ ecosystem-frontend.config.js" || echo "❌ ecosystem-frontend.config.js"
[ -d "/var/www/frontend-csdt/dist" ] && echo "✅ dist (compilado)" || echo "❌ dist (no compilado)"
echo ""

# Verificar servicios de IA
echo "=== SERVICIOS DE IA ==="
if [ -d "/var/www/frontend-csdt/src/services" ]; then
    SERVICES_COUNT=$(ls /var/www/frontend-csdt/src/services/ | wc -l)
    echo "✅ Servicios de IA: $SERVICES_COUNT servicios"
else
    echo "❌ Directorio de servicios no encontrado"
fi
echo ""

# Verificar logs
echo "=== LOGS RECIENTES ==="
if [ -f "/var/log/frontend-csdt/combined.log" ]; then
    echo "Últimas 5 líneas del log:"
    tail -n 5 /var/log/frontend-csdt/combined.log
else
    echo "❌ No hay logs disponibles"
fi
EOF

chmod +x ayuda/verificar_frontend.sh

print_message "✅ Script de verificación del frontend creado"

# ===========================================
# CREAR SCRIPT DE DESARROLLO DEL FRONTEND
# ===========================================
print_message "Creando script de desarrollo del frontend..."

cat > ayuda/desarrollo_frontend.sh << 'EOF'
#!/bin/bash
# Script para desarrollo del frontend CSDT

echo "Iniciando modo desarrollo del frontend CSDT..."

# Navegar al directorio del frontend
cd /var/www/frontend-csdt

# Verificar que existe el archivo package.json
if [ ! -f "package.json" ]; then
    echo "❌ Error: Archivo package.json no encontrado"
    exit 1
fi

# Instalar dependencias si es necesario
if [ ! -d "node_modules" ]; then
    echo "Instalando dependencias..."
    npm install
fi

# Iniciar servidor de desarrollo
echo "Iniciando servidor de desarrollo..."
npm run dev
EOF

chmod +x ayuda/desarrollo_frontend.sh

print_message "✅ Script de desarrollo del frontend creado"

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

chown -R www-data:www-data "$FRONTEND_DIR"
chmod -R 755 "$FRONTEND_DIR"
chmod -R 755 "$FRONTEND_DIR/ayuda"

print_message "✅ Permisos configurados"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_message "Verificando configuración..."

# Verificar que el archivo se creó correctamente
if [ -f "ecosystem-frontend.config.js" ]; then
    print_message "✅ Archivo ecosystem-frontend.config.js creado correctamente"
else
    print_error "❌ Error creando archivo ecosystem-frontend.config.js"
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
print_header "ECOSYSTEM-FRONTEND.CONFIG.JS DEL FRONTEND CREADO EXITOSAMENTE"

print_message "✅ Archivo ecosystem-frontend.config.js creado"
print_message "✅ Carpeta ayuda creada"
print_message "✅ Scripts de gestión creados"
print_message "✅ Permisos configurados"

print_warning "INFORMACIÓN DEL FRONTEND:"
print_warning "Directorio: $FRONTEND_DIR"
print_warning "Archivo: ecosystem-frontend.config.js"
print_warning "Carpeta ayuda: $FRONTEND_DIR/ayuda"
print_warning "Scripts disponibles:"
print_warning "  - ayuda/iniciar_frontend.sh"
print_warning "  - ayuda/detener_frontend.sh"
print_warning "  - ayuda/reiniciar_frontend.sh"
print_warning "  - ayuda/compilar_frontend.sh"
print_warning "  - ayuda/verificar_frontend.sh"
print_warning "  - ayuda/desarrollo_frontend.sh"

print_warning "COMANDOS ÚTILES:"
print_warning "Iniciar: ./ayuda/iniciar_frontend.sh"
print_warning "Detener: ./ayuda/detener_frontend.sh"
print_warning "Reiniciar: ./ayuda/reiniciar_frontend.sh"
print_warning "Compilar: ./ayuda/compilar_frontend.sh"
print_warning "Verificar: ./ayuda/verificar_frontend.sh"
print_warning "Desarrollo: ./ayuda/desarrollo_frontend.sh"

print_message "¡Ecosystem-frontend.config.js del frontend creado correctamente!"
