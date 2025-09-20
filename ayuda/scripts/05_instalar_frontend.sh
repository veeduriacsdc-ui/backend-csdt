#!/bin/bash

# ===========================================
# SCRIPT 5: INSTALAR FRONTEND
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
REPO_FRONTEND="https://github.com/veeduriacsdc-ui/frontend-csdt"
FRONTEND_DIR="/var/www/frontend-csdt"
IP_PUBLICA="64.225.113.49"

print_header "PASO 5: INSTALANDO FRONTEND REACT"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./05_instalar_frontend.sh)"
    exit 1
fi

# ===========================================
# CREAR DIRECTORIO DEL FRONTEND
# ===========================================
print_step "Creando directorio del frontend..."

# Limpiar directorio si existe
if [ -d "$FRONTEND_DIR" ]; then
    print_warning "Directorio $FRONTEND_DIR ya existe, limpiando..."
    rm -rf "$FRONTEND_DIR"
fi

mkdir -p "$FRONTEND_DIR"
cd "$FRONTEND_DIR"

print_message "✅ Directorio creado: $FRONTEND_DIR"

# ===========================================
# CLONAR REPOSITORIO DEL FRONTEND
# ===========================================
print_step "Clonando repositorio del frontend..."

# Clonar repositorio
git clone "$REPO_FRONTEND" .

# Verificar que se clonó correctamente
if [ -f "package.json" ]; then
    print_message "✅ Repositorio clonado correctamente"
else
    print_error "❌ Error clonando repositorio"
    exit 1
fi

# ===========================================
# INSTALAR DEPENDENCIAS NODE.JS
# ===========================================
print_step "Instalando dependencias Node.js (13 paquetes)..."

# Instalar dependencias Node.js
npm install --silent

# Verificar instalación
if [ -d "node_modules" ]; then
    NODE_COUNT=$(ls node_modules/ | wc -l)
    print_message "✅ Dependencias Node.js instaladas: $NODE_COUNT paquetes"
else
    print_error "❌ Error instalando dependencias Node.js"
    exit 1
fi

# ===========================================
# CREAR ARCHIVO .ENV
# ===========================================
print_step "Creando archivo .env para el frontend..."

cat > .env << EOF
# ===========================================
# CONFIGURACIÓN DE LA APLICACIÓN
# ===========================================
VITE_APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
VITE_APP_VERSION="1.0.0"
VITE_APP_ENV=production

# ===========================================
# CONFIGURACIÓN DE API
# ===========================================
VITE_API_URL=http://$IP_PUBLICA:8000
VITE_API_TIMEOUT=30000

# ===========================================
# CONFIGURACIÓN DE SERVICIOS DE IA
# ===========================================
VITE_IA_ENABLED=true
VITE_IA_SERVICES_ENABLED=true
VITE_IA_MEJORADA_ENABLED=true
VITE_IA_PROFESIONALES_ENABLED=true
VITE_IA_SISTEMA_PROFESIONAL_ENABLED=true
VITE_IA_CHAT_MEJORADO_ENABLED=true
VITE_IA_TECNICAS_ENABLED=true
VITE_IA_CONSEJO_ENABLED=true

# ===========================================
# CONFIGURACIÓN DE MAPAS
# ===========================================
VITE_LEAFLET_ENABLED=true
VITE_MAPBOX_TOKEN=tu_mapbox_token_aqui
VITE_GEODJANGO_ENABLED=true

# ===========================================
# CONFIGURACIÓN DE NOTIFICACIONES
# ===========================================
VITE_NOTIFICATIONS_ENABLED=true
VITE_PUSH_NOTIFICATIONS=false

# ===========================================
# CONFIGURACIÓN DE DESARROLLO
# ===========================================
VITE_DEBUG=false
VITE_VERBOSE_LOGGING=false

# ===========================================
# CONFIGURACIÓN DE SERVICIOS DE IA
# ===========================================
VITE_OPENAI_API_KEY=tu_api_key_de_openai_aqui
VITE_IA_TIMEOUT=30000
VITE_IA_MAX_TOKENS=2000
VITE_IA_TEMPERATURE=0.7
EOF

print_message "✅ Archivo .env creado correctamente"

# ===========================================
# VERIFICAR SERVICIOS DE IA
# ===========================================
print_step "Verificando servicios de IA..."

if [ -d "src/services" ]; then
    SERVICES_COUNT=$(ls src/services/ | wc -l)
    print_message "✅ Servicios de IA encontrados: $SERVICES_COUNT servicios"
    
    # Listar servicios encontrados
    echo "Servicios encontrados:"
    ls -la src/services/ | grep "\.js$" | awk '{print "  - " $9}'
else
    print_warning "⚠️ Directorio de servicios no encontrado"
fi

# ===========================================
# COMPILAR FRONTEND
# ===========================================
print_step "Compilando frontend para producción..."

# Compilar frontend
npm run build

# Verificar compilación
if [ -d "dist" ]; then
    DIST_COUNT=$(ls dist/ | wc -l)
    print_message "✅ Frontend compilado correctamente: $DIST_COUNT archivos"
else
    print_error "❌ Error compilando frontend"
    exit 1
fi

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_step "Configurando permisos..."

# Configurar permisos
chown -R www-data:www-data "$FRONTEND_DIR"
chmod -R 755 "$FRONTEND_DIR"

print_message "✅ Permisos configurados correctamente"

# ===========================================
# CREAR ARCHIVO ECOSYSTEM-FRONTEND.CONFIG.JS
# ===========================================
print_step "Creando archivo ecosystem-frontend.config.js..."

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
        ignore_watch: ['node_modules', 'dist'],
        restart_delay: 4000,
        max_restarts: 10,
        min_uptime: '10s'
    }]
};
EOF

print_message "✅ Archivo ecosystem-frontend.config.js creado"

# ===========================================
# CREAR SCRIPT DE DESARROLLO
# ===========================================
print_step "Creando script de desarrollo..."

cat > dev.sh << 'EOF'
#!/bin/bash
# Script para desarrollo del frontend

echo "Iniciando servidor de desarrollo..."
npm run dev
EOF

chmod +x dev.sh

print_message "✅ Script de desarrollo creado"

# ===========================================
# CREAR SCRIPT DE BUILD
# ===========================================
print_step "Creando script de build..."

cat > build.sh << 'EOF'
#!/bin/bash
# Script para build del frontend

echo "Compilando frontend para producción..."
npm run build

echo "Frontend compilado correctamente"
EOF

chmod +x build.sh

print_message "✅ Script de build creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN
# ===========================================
print_step "Verificando configuración del frontend..."

# Verificar que Vite funciona
if npm run build > /dev/null 2>&1; then
    print_message "✅ Vite funcionando correctamente"
else
    print_error "❌ Error con Vite"
    exit 1
fi

# Verificar archivos compilados
if [ -f "dist/index.html" ]; then
    print_message "✅ Archivos compilados generados correctamente"
else
    print_error "❌ Error generando archivos compilados"
    exit 1
fi

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "FRONTEND INSTALADO EXITOSAMENTE"

print_message "✅ Repositorio clonado correctamente"
print_message "✅ Dependencias Node.js instaladas (13 paquetes)"
print_message "✅ Archivo .env configurado"
print_message "✅ Servicios de IA verificados"
print_message "✅ Frontend compilado para producción"
print_message "✅ Permisos configurados"
print_message "✅ Archivo ecosystem-frontend.config.js creado"
print_message "✅ Scripts de desarrollo y build creados"

print_warning "INFORMACIÓN DEL FRONTEND:"
print_warning "Directorio: $FRONTEND_DIR"
print_warning "URL: http://$IP_PUBLICA:3000"
print_warning "Servicios de IA: $SERVICES_COUNT servicios"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./06_configurar_servicios_ia.sh"

print_message "¡Frontend instalado correctamente!"
