#!/bin/bash

# ===========================================
# SCRIPT DE VERIFICACIÓN Y DIAGNÓSTICO CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

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

# Información del servidor
IP_PUBLICA="64.225.113.49"

print_header "VERIFICACIÓN Y DIAGNÓSTICO CSDT"

# ===========================================
# VERIFICAR SERVICIOS DEL SISTEMA
# ===========================================
print_header "VERIFICANDO SERVICIOS DEL SISTEMA"

print_message "Verificando PHP..."
php --version || print_error "PHP no está instalado"

print_message "Verificando Composer..."
composer --version || print_error "Composer no está instalado"

print_message "Verificando Node.js..."
node --version || print_error "Node.js no está instalado"

print_message "Verificando npm..."
npm --version || print_error "npm no está instalado"

print_message "Verificando Git..."
git --version || print_error "Git no está instalado"

print_message "Verificando MySQL..."
mysql --version || print_error "MySQL no está instalado"

print_message "Verificando PM2..."
pm2 --version || print_error "PM2 no está instalado"

# ===========================================
# VERIFICAR ESTADO DE PM2
# ===========================================
print_header "VERIFICANDO ESTADO DE PM2"

print_message "Estado de aplicaciones PM2:"
pm2 status

print_message "Verificando logs del backend..."
pm2 logs backend-csdt --lines 10

print_message "Verificando logs del frontend..."
pm2 logs frontend-csdt --lines 10

# ===========================================
# VERIFICAR CONECTIVIDAD
# ===========================================
print_header "VERIFICANDO CONECTIVIDAD"

print_message "Verificando API local..."
curl -I http://localhost:8000 && print_message "✅ API local responde" || print_error "❌ API local no responde"

print_message "Verificando Frontend local..."
curl -I http://localhost:3000 && print_message "✅ Frontend local responde" || print_error "❌ Frontend local no responde"

print_message "Verificando API externa..."
curl -I http://$IP_PUBLICA:8000 && print_message "✅ API externa responde" || print_error "❌ API externa no responde"

print_message "Verificando Frontend externo..."
curl -I http://$IP_PUBLICA:3000 && print_message "✅ Frontend externo responde" || print_error "❌ Frontend externo no responde"

# ===========================================
# VERIFICAR ARCHIVOS DE CONFIGURACIÓN
# ===========================================
print_header "VERIFICANDO ARCHIVOS DE CONFIGURACIÓN"

print_message "Verificando archivo .env del backend..."
if [ -f "/var/www/backend-csdt/.env" ]; then
    print_message "✅ Archivo .env del backend existe"
    print_message "Verificando configuración de base de datos..."
    grep "DB_CONNECTION=mysql" /var/www/backend-csdt/.env && print_message "✅ Base de datos configurada como MySQL" || print_error "❌ Base de datos no configurada como MySQL"
    grep "APP_KEY=" /var/www/backend-csdt/.env && print_message "✅ Clave de aplicación configurada" || print_warning "⚠️ Clave de aplicación no configurada"
else
    print_error "❌ Archivo .env del backend no existe"
fi

print_message "Verificando archivo .env del frontend..."
if [ -f "/var/www/frontend-csdt/.env" ]; then
    print_message "✅ Archivo .env del frontend existe"
    print_message "Verificando configuración de API..."
    grep "VITE_API_URL=http://$IP_PUBLICA:8000" /var/www/frontend-csdt/.env && print_message "✅ URL de API configurada correctamente" || print_error "❌ URL de API no configurada correctamente"
else
    print_error "❌ Archivo .env del frontend no existe"
fi

# ===========================================
# VERIFICAR SERVICIOS DE IA
# ===========================================
print_header "VERIFICANDO SERVICIOS DE IA"

print_message "Verificando servicios de IA en el frontend..."
if [ -d "/var/www/frontend-csdt/src/services" ]; then
    print_message "✅ Directorio de servicios existe"
    
    # Verificar servicios específicos
    services=("IAMejoradaService.js" "IAsProfesionalesService.js" "SistemaIAProfesionalService.js" "ChatGPTMejoradoService.js" "IAsTecnicasService.js" "ConsejoIAService.js" "AnalisisJuridicoService.js" "AnalisisNarrativoProfesionalService.js")
    
    for service in "${services[@]}"; do
        if [ -f "/var/www/frontend-csdt/src/services/$service" ]; then
            print_message "✅ $service existe"
        else
            print_warning "⚠️ $service no encontrado"
        fi
    done
else
    print_error "❌ Directorio de servicios no existe"
fi

# ===========================================
# VERIFICAR BASE DE DATOS
# ===========================================
print_header "VERIFICANDO BASE DE DATOS"

print_message "Verificando conexión a MySQL..."
mysql -u csdt -pcsdt_password_2024 -e "SHOW DATABASES;" 2>/dev/null && print_message "✅ Conexión a MySQL exitosa" || print_error "❌ No se puede conectar a MySQL"

print_message "Verificando base de datos csdt_final..."
mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SHOW TABLES;" 2>/dev/null && print_message "✅ Base de datos csdt_final accesible" || print_error "❌ Base de datos csdt_final no accesible"

# ===========================================
# VERIFICAR PERMISOS
# ===========================================
print_header "VERIFICANDO PERMISOS"

print_message "Verificando permisos del backend..."
if [ -d "/var/www/backend-csdt" ]; then
    owner=$(stat -c '%U:%G' /var/www/backend-csdt)
    if [ "$owner" = "www-data:www-data" ]; then
        print_message "✅ Permisos del backend correctos"
    else
        print_warning "⚠️ Permisos del backend incorrectos: $owner"
    fi
else
    print_error "❌ Directorio del backend no existe"
fi

print_message "Verificando permisos del frontend..."
if [ -d "/var/www/frontend-csdt" ]; then
    owner=$(stat -c '%U:%G' /var/www/frontend-csdt)
    if [ "$owner" = "www-data:www-data" ]; then
        print_message "✅ Permisos del frontend correctos"
    else
        print_warning "⚠️ Permisos del frontend incorrectos: $owner"
    fi
else
    print_error "❌ Directorio del frontend no existe"
fi

# ===========================================
# VERIFICAR FIREWALL
# ===========================================
print_header "VERIFICANDO FIREWALL"

print_message "Estado del firewall UFW:"
ufw status

# ===========================================
# DIAGNÓSTICO AVANZADO
# ===========================================
print_header "DIAGNÓSTICO AVANZADO"

print_message "Verificando uso de memoria..."
free -h

print_message "Verificando uso de disco..."
df -h

print_message "Verificando procesos de Node.js..."
ps aux | grep node

print_message "Verificando procesos de PHP..."
ps aux | grep php

# ===========================================
# COMANDOS DE SOLUCIÓN DE PROBLEMAS
# ===========================================
print_header "COMANDOS DE SOLUCIÓN DE PROBLEMAS"

print_message "Si hay problemas, ejecuta estos comandos:"
print_message "1. Reiniciar PM2: pm2 restart all"
print_message "2. Ver logs: pm2 logs"
print_message "3. Reiniciar MySQL: systemctl restart mysql"
print_message "4. Verificar permisos: chown -R www-data:www-data /var/www/"
print_message "5. Limpiar cache Laravel: cd /var/www/backend-csdt && php artisan config:clear"

print_header "VERIFICACIÓN COMPLETADA"
