#!/bin/bash

# ===========================================
# SCRIPT DE VERIFICACIÓN CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

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

print_header "VERIFICACIÓN CSDT"

# ===========================================
# VERIFICAR SERVICIOS PM2
# ===========================================
print_message "Verificando servicios PM2..."
pm2 status

# ===========================================
# VERIFICAR CONECTIVIDAD LOCAL
# ===========================================
print_message "Verificando conectividad local..."

# Backend local
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "✅ Backend local: http://localhost:8000"
else
    print_error "❌ Backend local no responde"
fi

# Frontend local
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend local: http://localhost:3000"
else
    print_error "❌ Frontend local no responde"
fi

# ===========================================
# VERIFICAR CONECTIVIDAD EXTERNA
# ===========================================
print_message "Verificando conectividad externa..."

# Backend externo
if curl -s http://64.225.113.49:8000 > /dev/null 2>&1; then
    print_success "✅ Backend externo: http://64.225.113.49:8000"
else
    print_error "❌ Backend externo no accesible"
fi

# Frontend externo
if curl -s http://64.225.113.49:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend externo: http://64.225.113.49:3000"
else
    print_error "❌ Frontend externo no accesible"
fi

# ===========================================
# VERIFICAR BASE DE DATOS
# ===========================================
print_message "Verificando base de datos..."

cd /var/www/backend-csdt
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión exitosa';" > /dev/null 2>&1; then
    print_success "✅ Base de datos conectada"
else
    print_error "❌ Error de conexión a base de datos"
fi

# ===========================================
# VERIFICAR ARCHIVOS
# ===========================================
print_message "Verificando archivos importantes..."

# Backend
if [ -f "/var/www/backend-csdt/.env" ]; then
    print_success "✅ Archivo .env del backend"
else
    print_error "❌ Archivo .env del backend no encontrado"
fi

if [ -f "/var/www/backend-csdt/ecosystem.config.js" ]; then
    print_success "✅ Archivo ecosystem.config.js del backend"
else
    print_error "❌ Archivo ecosystem.config.js del backend no encontrado"
fi

# Frontend
if [ -f "/var/www/frontend-csdt/.env" ]; then
    print_success "✅ Archivo .env del frontend"
else
    print_error "❌ Archivo .env del frontend no encontrado"
fi

if [ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ]; then
    print_success "✅ Archivo ecosystem-frontend.config.js del frontend"
else
    print_error "❌ Archivo ecosystem-frontend.config.js del frontend no encontrado"
fi

# ===========================================
# VERIFICAR PERMISOS
# ===========================================
print_message "Verificando permisos..."

# Backend
if [ -w "/var/www/backend-csdt/storage" ]; then
    print_success "✅ Permisos de escritura en storage del backend"
else
    print_error "❌ Sin permisos de escritura en storage del backend"
fi

# Frontend
if [ -w "/var/www/frontend-csdt/dist" ]; then
    print_success "✅ Permisos de escritura en dist del frontend"
else
    print_error "❌ Sin permisos de escritura en dist del frontend"
fi

# ===========================================
# VERIFICAR LOGS
# ===========================================
print_message "Verificando logs..."

if [ -f "/var/log/backend-csdt/error.log" ]; then
    print_success "✅ Logs del backend disponibles"
    echo "Últimas 5 líneas del log de errores del backend:"
    tail -5 /var/log/backend-csdt/error.log
else
    print_warning "⚠️ Logs del backend no encontrados"
fi

if [ -f "/var/log/frontend-csdt/error.log" ]; then
    print_success "✅ Logs del frontend disponibles"
    echo "Últimas 5 líneas del log de errores del frontend:"
    tail -5 /var/log/frontend-csdt/error.log
else
    print_warning "⚠️ Logs del frontend no encontrados"
fi

# ===========================================
# VERIFICAR FIREWALL
# ===========================================
print_message "Verificando firewall..."
ufw status

# ===========================================
# VERIFICAR RECURSOS DEL SISTEMA
# ===========================================
print_message "Verificando recursos del sistema..."

echo "Memoria disponible:"
free -h

echo "Espacio en disco:"
df -h /

echo "Uso de CPU:"
top -bn1 | grep "Cpu(s)"

print_header "VERIFICACIÓN COMPLETADA"

print_message "Para más detalles, revisa los logs con:"
print_message "pm2 logs backend-csdt"
print_message "pm2 logs frontend-csdt"
