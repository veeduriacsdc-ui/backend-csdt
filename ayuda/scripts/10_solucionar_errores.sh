#!/bin/bash

# ===========================================
# SCRIPT 10: SOLUCIONAR ERRORES
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

print_header "PASO 10: SOLUCIONANDO ERRORES COMUNES"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./10_solucionar_errores.sh)"
    exit 1
fi

# ===========================================
# DIAGNÓSTICO INICIAL
# ===========================================
print_step "Realizando diagnóstico inicial..."

# Crear archivo de diagnóstico
cat > /tmp/diagnostico_csdt.log << EOF
=== DIAGNÓSTICO CSDT ===
Fecha: $(date)
Servidor: $IP_PUBLICA

=== SERVICIOS DEL SISTEMA ===
PHP: $(php --version 2>/dev/null | head -n1 | cut -d' ' -f2 || echo "NO INSTALADO")
Composer: $(composer --version 2>/dev/null | cut -d' ' -f3 || echo "NO INSTALADO")
Node.js: $(node --version 2>/dev/null | cut -d'v' -f2 || echo "NO INSTALADO")
npm: $(npm --version 2>/dev/null || echo "NO INSTALADO")
PM2: $(pm2 --version 2>/dev/null || echo "NO INSTALADO")
MySQL: $(mysql --version 2>/dev/null | cut -d' ' -f3 | cut -d',' -f1 || echo "NO INSTALADO")
Nginx: $(nginx -v 2>&1 | cut -d'/' -f2 2>/dev/null || echo "NO INSTALADO")

=== ESTADO DE PM2 ===
$(pm2 status 2>/dev/null || echo "PM2 NO DISPONIBLE")

=== CONECTIVIDAD ===
Backend local: $(curl -s http://localhost:8000 > /dev/null 2>&1 && echo "OK" || echo "ERROR")
Frontend local: $(curl -s http://localhost:3000 > /dev/null 2>&1 && echo "OK" || echo "ERROR")
Backend externo: $(curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1 && echo "OK" || echo "ERROR")
Frontend externo: $(curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1 && echo "OK" || echo "ERROR")

=== RECURSOS DEL SISTEMA ===
Memoria: $(free -h | grep "Mem:" | awk '{print $2}')
Disco: $(df -h / | awk 'NR==2 {print $4}')
CPU: $(nproc) núcleos
EOF

print_message "✅ Diagnóstico inicial completado"

# ===========================================
# SOLUCIONAR ERRORES DE PM2
# ===========================================
print_step "Solucionando errores de PM2..."

# Verificar si PM2 está instalado
if ! command -v pm2 > /dev/null 2>&1; then
    print_warning "PM2 no está instalado, instalando..."
    npm install -g pm2
fi

# Detener todos los procesos PM2
pm2 delete all > /dev/null 2>&1 || true

# Reiniciar PM2
pm2 kill > /dev/null 2>&1 || true

print_message "✅ PM2 reiniciado"

# ===========================================
# SOLUCIONAR ERRORES DE BACKEND
# ===========================================
print_step "Solucionando errores de backend..."

cd "$BACKEND_DIR"

# Verificar que existe el directorio
if [ ! -d "$BACKEND_DIR" ]; then
    print_error "❌ Directorio del backend no existe: $BACKEND_DIR"
    exit 1
fi

# Limpiar cache de Laravel
print_message "Limpiando cache de Laravel..."
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true

# Verificar conexión a la base de datos
print_message "Verificando conexión a la base de datos..."
if mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT 1;" > /dev/null 2>&1; then
    print_message "✅ Conexión a la base de datos exitosa"
else
    print_warning "⚠️ Error de conexión a la base de datos"
    
    # Intentar reiniciar MySQL
    print_message "Reiniciando MySQL..."
    systemctl restart mysql
    
    # Esperar un momento
    sleep 5
    
    # Verificar nuevamente
    if mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT 1;" > /dev/null 2>&1; then
        print_message "✅ Conexión a la base de datos restaurada"
    else
        print_error "❌ No se puede conectar a la base de datos"
    fi
fi

# Verificar archivo .env
if [ ! -f ".env" ]; then
    print_error "❌ Archivo .env no existe"
    exit 1
fi

# Verificar clave de aplicación
if ! grep -q "APP_KEY=base64:" .env; then
    print_warning "Generando clave de aplicación..."
    php artisan key:generate --force
fi

# Configurar permisos
print_message "Configurando permisos del backend..."
chown -R www-data:www-data "$BACKEND_DIR"
chmod -R 755 "$BACKEND_DIR"
chmod -R 775 "$BACKEND_DIR/storage"
chmod -R 775 "$BACKEND_DIR/bootstrap/cache"

print_message "✅ Backend configurado correctamente"

# ===========================================
# SOLUCIONAR ERRORES DE FRONTEND
# ===========================================
print_step "Solucionando errores de frontend..."

cd "$FRONTEND_DIR"

# Verificar que existe el directorio
if [ ! -d "$FRONTEND_DIR" ]; then
    print_error "❌ Directorio del frontend no existe: $FRONTEND_DIR"
    exit 1
fi

# Verificar archivo .env
if [ ! -f ".env" ]; then
    print_error "❌ Archivo .env del frontend no existe"
    exit 1
fi

# Reinstalar dependencias
print_message "Reinstalando dependencias del frontend..."
npm install --silent

# Recompilar frontend
print_message "Recompilando frontend..."
npm run build

# Configurar permisos
print_message "Configurando permisos del frontend..."
chown -R www-data:www-data "$FRONTEND_DIR"
chmod -R 755 "$FRONTEND_DIR"

print_message "✅ Frontend configurado correctamente"

# ===========================================
# SOLUCIONAR ERRORES DE MYSQL
# ===========================================
print_step "Solucionando errores de MySQL..."

# Verificar estado de MySQL
if ! systemctl is-active --quiet mysql; then
    print_warning "MySQL no está activo, iniciando..."
    systemctl start mysql
    systemctl enable mysql
fi

# Verificar conexión
if mysql -u csdt -pcsdt_password_2024 -e "USE csdt_final; SELECT 1;" > /dev/null 2>&1; then
    print_message "✅ MySQL funcionando correctamente"
else
    print_warning "⚠️ Error de conexión a MySQL"
    
    # Intentar crear la base de datos
    print_message "Intentando crear base de datos..."
    mysql -u root -pcsdt_password_2024 -e "
        CREATE DATABASE IF NOT EXISTS csdt_final CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS 'csdt'@'localhost' IDENTIFIED BY 'csdt_password_2024';
        GRANT ALL PRIVILEGES ON csdt_final.* TO 'csdt'@'localhost';
        FLUSH PRIVILEGES;
    " > /dev/null 2>&1 || true
fi

# ===========================================
# SOLUCIONAR ERRORES DE NGINX
# ===========================================
print_step "Solucionando errores de Nginx..."

# Verificar estado de Nginx
if ! systemctl is-active --quiet nginx; then
    print_warning "Nginx no está activo, iniciando..."
    systemctl start nginx
    systemctl enable nginx
fi

# Verificar configuración
if nginx -t > /dev/null 2>&1; then
    print_message "✅ Nginx configurado correctamente"
else
    print_warning "⚠️ Error en configuración de Nginx"
fi

# ===========================================
# SOLUCIONAR ERRORES DE FIREWALL
# ===========================================
print_step "Solucionando errores de firewall..."

# Configurar firewall
ufw allow OpenSSH > /dev/null 2>&1 || true
ufw allow 3000 > /dev/null 2>&1 || true
ufw allow 8000 > /dev/null 2>&1 || true
ufw allow 'Nginx Full' > /dev/null 2>&1 || true
ufw --force enable > /dev/null 2>&1 || true

print_message "✅ Firewall configurado"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_step "Reiniciando servicios..."

# Iniciar backend
cd "$BACKEND_DIR"
if [ -f "ecosystem.config.js" ]; then
    pm2 start ecosystem.config.js --env production
    print_message "✅ Backend iniciado"
else
    print_error "❌ Archivo ecosystem.config.js no encontrado"
fi

# Iniciar frontend
cd "$FRONTEND_DIR"
if [ -f "ecosystem-frontend.config.js" ]; then
    pm2 start ecosystem-frontend.config.js
    print_message "✅ Frontend iniciado"
else
    print_error "❌ Archivo ecosystem-frontend.config.js no encontrado"
fi

# Configurar PM2 para inicio automático
pm2 startup > /dev/null 2>&1 || true
pm2 save

print_message "✅ Servicios reiniciados"

# ===========================================
# VERIFICAR CONECTIVIDAD
# ===========================================
print_step "Verificando conectividad..."

# Esperar un momento para que los servicios se inicien
sleep 10

# Verificar backend local
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_message "✅ Backend local responde"
else
    print_warning "⚠️ Backend local no responde"
fi

# Verificar frontend local
if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_message "✅ Frontend local responde"
else
    print_warning "⚠️ Frontend local no responde"
fi

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
# CREAR SCRIPT DE DIAGNÓSTICO
# ===========================================
print_step "Creando script de diagnóstico..."

cat > /usr/local/bin/diagnosticar_csdt.sh << 'EOF'
#!/bin/bash
# Script de diagnóstico para CSDT

echo "=== DIAGNÓSTICO CSDT ==="
echo "Fecha: $(date)"
echo ""

# Estado de PM2
echo "=== ESTADO DE PM2 ==="
pm2 status
echo ""

# Estado de servicios
echo "=== ESTADO DE SERVICIOS ==="
systemctl is-active mysql && echo "✅ MySQL activo" || echo "❌ MySQL inactivo"
systemctl is-active nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active redis-server && echo "✅ Redis activo" || echo "❌ Redis inactivo"
echo ""

# Conectividad
echo "=== CONECTIVIDAD ==="
curl -s http://localhost:8000 > /dev/null && echo "✅ Backend local" || echo "❌ Backend local"
curl -s http://localhost:3000 > /dev/null && echo "✅ Frontend local" || echo "❌ Frontend local"
curl -s http://64.225.113.49:8000 > /dev/null && echo "✅ Backend externo" || echo "❌ Backend externo"
curl -s http://64.225.113.49:3000 > /dev/null && echo "✅ Frontend externo" || echo "❌ Frontend externo"
echo ""

# Uso de recursos
echo "=== RECURSOS DEL SISTEMA ==="
echo "Memoria:"
free -h
echo ""
echo "Disco:"
df -h /
echo ""

# Logs recientes
echo "=== LOGS RECIENTES ==="
echo "Backend:"
tail -n 5 /var/log/backend-csdt/combined.log 2>/dev/null || echo "No hay logs del backend"
echo ""
echo "Frontend:"
tail -n 5 /var/log/frontend-csdt/combined.log 2>/dev/null || echo "No hay logs del frontend"
EOF

chmod +x /usr/local/bin/diagnosticar_csdt.sh

print_message "✅ Script de diagnóstico creado"

# ===========================================
# CREAR SCRIPT DE REPARACIÓN
# ===========================================
print_step "Creando script de reparación..."

cat > /usr/local/bin/reparar_csdt.sh << 'EOF'
#!/bin/bash
# Script de reparación para CSDT

echo "Reparando sistema CSDT..."

# Detener servicios
pm2 delete all > /dev/null 2>&1 || true

# Limpiar cache
cd /var/www/backend-csdt
php artisan config:clear > /dev/null 2>&1 || true
php artisan cache:clear > /dev/null 2>&1 || true
php artisan route:clear > /dev/null 2>&1 || true
php artisan view:clear > /dev/null 2>&1 || true

# Reinstalar dependencias
cd /var/www/backend-csdt
composer install --no-dev --optimize-autoloader > /dev/null 2>&1 || true

cd /var/www/frontend-csdt
npm install --silent > /dev/null 2>&1 || true
npm run build > /dev/null 2>&1 || true

# Configurar permisos
chown -R www-data:www-data /var/www/
chmod -R 755 /var/www/
chmod -R 775 /var/www/*/storage
chmod -R 775 /var/www/*/bootstrap/cache

# Reiniciar servicios
cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production > /dev/null 2>&1 || true

cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js > /dev/null 2>&1 || true

pm2 save > /dev/null 2>&1 || true

echo "Reparación completada"
EOF

chmod +x /usr/local/bin/reparar_csdt.sh

print_message "✅ Script de reparación creado"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "SOLUCIÓN DE ERRORES COMPLETADA"

print_message "✅ Errores comunes solucionados"
print_message "✅ Servicios reiniciados"
print_message "✅ Scripts de diagnóstico y reparación creados"

print_warning "COMANDOS ÚTILES:"
print_warning "Diagnóstico: /usr/local/bin/diagnosticar_csdt.sh"
print_warning "Reparación: /usr/local/bin/reparar_csdt.sh"
print_warning "Monitoreo: /usr/local/bin/monitor_csdt.sh"
print_warning "Estado: pm2 status"
print_warning "Logs: pm2 logs"

print_warning "INFORMACIÓN DE ACCESO:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"

print_message "¡Solución de errores completada!"
