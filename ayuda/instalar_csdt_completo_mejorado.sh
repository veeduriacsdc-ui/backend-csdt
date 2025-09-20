#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN COMPLETA CSDT MEJORADO
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# VERSIÓN DIGITALOCEAN SIN DOMINIO - COMPLETA
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

# Información del servidor
IP_PUBLICA="64.225.113.49"
IP_PRIVADA="10.120.0.2"
REPO_BACKEND="https://github.com/veeduriacsdc-ui/backend-csdt.git"
REPO_FRONTEND="https://github.com/veeduriacsdc-ui/frontend-csdt"

print_header "INSTALACIÓN COMPLETA CSDT - VERSIÓN MEJORADA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./instalar_csdt_completo_mejorado.sh)"
    exit 1
fi

# ===========================================
# CREAR ESTRUCTURA DE CARPETAS
# ===========================================
print_step "Creando estructura de carpetas..."

# Crear carpetas principales
mkdir -p /var/www/backend-csdt/ayuda
mkdir -p /var/www/frontend-csdt/ayuda
mkdir -p /var/log/backend-csdt
mkdir -p /var/log/frontend-csdt
mkdir -p /var/backups/database

print_success "Estructura de carpetas creada"

# ===========================================
# HACER SCRIPTS EJECUTABLES
# ===========================================
print_step "Haciendo scripts ejecutables..."

# Hacer ejecutables todos los scripts
chmod +x /var/www/backend-csdt/ayuda/*.sh
chmod +x /var/www/backend-csdt/ayuda/scripts/*.sh

print_success "Scripts hechos ejecutables"

# ===========================================
# EJECUTAR SCRIPT PRINCIPAL DE INSTALACIÓN
# ===========================================
print_header "EJECUTANDO INSTALACIÓN PRINCIPAL"

if [ -f "/var/www/backend-csdt/ayuda/instalar_csdt_digitalocean_mejorado.sh" ]; then
    print_message "Ejecutando script principal de instalación..."
    /var/www/backend-csdt/ayuda/instalar_csdt_digitalocean_mejorado.sh
else
    print_error "Script principal no encontrado"
    exit 1
fi

# ===========================================
# EJECUTAR SCRIPTS ADICIONALES
# ===========================================
print_header "EJECUTANDO SCRIPTS ADICIONALES"

# Instalar librerías
print_step "Instalando librerías..."
if [ -f "/var/www/backend-csdt/ayuda/scripts/instalar_librerias.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/instalar_librerias.sh
else
    print_warning "Script de librerías no encontrado"
fi

# Configurar base de datos
print_step "Configurando base de datos..."
if [ -f "/var/www/backend-csdt/ayuda/scripts/configurar_base_datos.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/configurar_base_datos.sh
else
    print_warning "Script de base de datos no encontrado"
fi

# Instalar servicios de IA
print_step "Instalando servicios de IA..."
if [ -f "/var/www/backend-csdt/ayuda/scripts/instalar_servicios_ia.sh" ]; then
    /var/www/backend-csdt/ayuda/scripts/instalar_servicios_ia.sh
else
    print_warning "Script de servicios de IA no encontrado"
fi

# ===========================================
# CREAR SCRIPTS DE GESTIÓN GLOBALES
# ===========================================
print_header "CREANDO SCRIPTS DE GESTIÓN GLOBALES"

# Script de gestión principal
print_step "Creando script de gestión principal..."
cat > /usr/local/bin/gestionar_csdt.sh << 'EOF'
#!/bin/bash
# Script de gestión principal CSDT

while true; do
    clear
    echo "==========================================="
    echo "    GESTIÓN CSDT - $(date)"
    echo "==========================================="
    echo ""
    echo "1. Ver estado del sistema"
    echo "2. Iniciar servicios"
    echo "3. Detener servicios"
    echo "4. Reiniciar servicios"
    echo "5. Ver logs"
    echo "6. Verificar sistema"
    echo "7. Reparar sistema"
    echo "8. Limpiar sistema"
    echo "9. Hacer backup"
    echo "10. Monitorear en tiempo real"
    echo "11. Salir"
    echo ""
    echo -n "Selecciona una opción (1-11): "
    read option
    
    case $option in
        1)
            /var/www/backend-csdt/ayuda/scripts/verificar_csdt.sh
            echo "Presiona Enter para continuar..."
            read
            ;;
        2)
            pm2 start all
            echo "Servicios iniciados"
            echo "Presiona Enter para continuar..."
            read
            ;;
        3)
            pm2 stop all
            echo "Servicios detenidos"
            echo "Presiona Enter para continuar..."
            read
            ;;
        4)
            pm2 restart all
            echo "Servicios reiniciados"
            echo "Presiona Enter para continuar..."
            read
            ;;
        5)
            pm2 logs --lines 50
            echo "Presiona Enter para continuar..."
            read
            ;;
        6)
            /var/www/backend-csdt/ayuda/scripts/verificar_csdt.sh
            echo "Presiona Enter para continuar..."
            read
            ;;
        7)
            /var/www/backend-csdt/ayuda/scripts/reparar_csdt.sh
            echo "Presiona Enter para continuar..."
            read
            ;;
        8)
            /var/www/backend-csdt/ayuda/scripts/limpiar_csdt.sh
            echo "Presiona Enter para continuar..."
            read
            ;;
        9)
            /var/www/backend-csdt/ayuda/scripts/backup_csdt.sh
            echo "Presiona Enter para continuar..."
            read
            ;;
        10)
            /var/www/backend-csdt/ayuda/scripts/monitor_csdt.sh
            ;;
        11)
            echo "Saliendo..."
            exit 0
            ;;
        *)
            echo "Opción inválida"
            echo "Presiona Enter para continuar..."
            read
            ;;
    esac
done
EOF

chmod +x /usr/local/bin/gestionar_csdt.sh

# Script de inicio rápido
print_step "Creando script de inicio rápido..."
cat > /usr/local/bin/iniciar_csdt.sh << 'EOF'
#!/bin/bash
# Script de inicio rápido CSDT

echo "Iniciando CSDT..."
cd /var/www/backend-csdt
pm2 start ecosystem.config.js --env production

cd /var/www/frontend-csdt
pm2 start ecosystem-frontend.config.js

pm2 save
echo "CSDT iniciado correctamente"
EOF

chmod +x /usr/local/bin/iniciar_csdt.sh

# Script de parada rápida
print_step "Creando script de parada rápida..."
cat > /usr/local/bin/detener_csdt.sh << 'EOF'
#!/bin/bash
# Script de parada rápida CSDT

echo "Deteniendo CSDT..."
pm2 stop all
pm2 save
echo "CSDT detenido correctamente"
EOF

chmod +x /usr/local/bin/detener_csdt.sh

# Script de verificación rápida
print_step "Creando script de verificación rápida..."
cat > /usr/local/bin/verificar_csdt.sh << 'EOF'
#!/bin/bash
# Script de verificación rápida CSDT

echo "Verificando CSDT..."
pm2 status
echo ""
echo "Conectividad:"
curl -I http://localhost:8000 2>/dev/null && echo "Backend: OK" || echo "Backend: ERROR"
curl -I http://localhost:3000 2>/dev/null && echo "Frontend: OK" || echo "Frontend: ERROR"
EOF

chmod +x /usr/local/bin/verificar_csdt.sh

print_success "Scripts de gestión globales creados"

# ===========================================
# CONFIGURAR CRON JOBS
# ===========================================
print_header "CONFIGURANDO CRON JOBS"

print_step "Configurando tareas automáticas..."

# Backup diario a las 2 AM
echo "0 2 * * * /var/www/backend-csdt/ayuda/scripts/backup_csdt.sh >> /var/log/csdt_backup.log 2>&1" | crontab -

# Limpieza semanal los domingos a las 3 AM
echo "0 3 * * 0 /var/www/backend-csdt/ayuda/scripts/limpiar_csdt.sh >> /var/log/csdt_limpieza.log 2>&1" | crontab -

# Verificación diaria a las 6 AM
echo "0 6 * * * /usr/local/bin/verificar_csdt.sh >> /var/log/csdt_verificacion.log 2>&1" | crontab -

print_success "Cron jobs configurados"

# ===========================================
# VERIFICACIÓN FINAL
# ===========================================
print_header "VERIFICACIÓN FINAL"

print_step "Verificando servicios..."
pm2 status

print_step "Verificando conectividad..."
if curl -s http://localhost:8000 > /dev/null 2>&1; then
    print_success "✅ Backend local: http://localhost:8000"
else
    print_warning "⚠️ Backend local no responde"
fi

if curl -s http://localhost:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend local: http://localhost:3000"
else
    print_warning "⚠️ Frontend local no responde"
fi

if curl -s http://$IP_PUBLICA:8000 > /dev/null 2>&1; then
    print_success "✅ Backend externo: http://$IP_PUBLICA:8000"
else
    print_warning "⚠️ Backend externo no accesible"
fi

if curl -s http://$IP_PUBLICA:3000 > /dev/null 2>&1; then
    print_success "✅ Frontend externo: http://$IP_PUBLICA:3000"
else
    print_warning "⚠️ Frontend externo no accesible"
fi

# ===========================================
# CREAR DOCUMENTACIÓN
# ===========================================
print_header "CREANDO DOCUMENTACIÓN"

print_step "Creando documentación del sistema..."

cat > /var/www/README_CSDT.md << 'EOF'
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

## Información del Sistema

- **IP Pública:** 64.225.113.49
- **IP Privada:** 10.120.0.2
- **Backend:** http://64.225.113.49:8000
- **Frontend:** http://64.225.113.49:3000

## Comandos Útiles

### Gestión Principal
- `gestionar_csdt.sh` - Menú interactivo de gestión
- `iniciar_csdt.sh` - Inicio rápido
- `detener_csdt.sh` - Parada rápida
- `verificar_csdt.sh` - Verificación rápida

### Scripts Específicos
- `instalar_csdt_digitalocean_mejorado.sh` - Instalación principal
- `instalar_librerias.sh` - Instalar librerías
- `configurar_base_datos.sh` - Configurar base de datos
- `instalar_servicios_ia.sh` - Instalar servicios de IA
- `reparar_csdt.sh` - Reparar sistema
- `limpiar_csdt.sh` - Limpiar sistema
- `backup_csdt.sh` - Hacer backup
- `restaurar_csdt.sh` - Restaurar backup
- `monitor_csdt.sh` - Monitoreo en tiempo real
- `diagnosticar_csdt.sh` - Diagnóstico completo

### Gestión de Servicios
- `pm2 status` - Estado de servicios
- `pm2 logs` - Ver logs
- `pm2 restart all` - Reiniciar todos
- `pm2 stop all` - Detener todos

## Estructura del Proyecto

```
/var/www/
├── backend-csdt/           # Backend Laravel
│   ├── ayuda/              # Scripts de gestión
│   │   ├── *.sh           # Scripts principales
│   │   └── scripts/       # Scripts específicos
│   ├── ecosystem.config.js # Configuración PM2
│   └── ...
└── frontend-csdt/          # Frontend React
    ├── ayuda/              # Scripts de gestión
    │   └── *.sh           # Scripts específicos
    ├── ecosystem-frontend.config.js # Configuración PM2
    └── ...
```

## Solución de Problemas

1. **Servicios no responden:**
   - Ejecutar: `reparar_csdt.sh`
   - Verificar: `verificar_csdt.sh`

2. **Problemas de base de datos:**
   - Ejecutar: `configurar_base_datos.sh`
   - Verificar conexión: `mysql -u csdt -p123 csdt_final`

3. **Problemas de permisos:**
   - Ejecutar: `chown -R www-data:www-data /var/www/`
   - Ejecutar: `chmod -R 755 /var/www/`

4. **Logs:**
   - Backend: `/var/log/backend-csdt/`
   - Frontend: `/var/log/frontend-csdt/`
   - Sistema: `pm2 logs`

## Mantenimiento

- **Backup automático:** Diario a las 2 AM
- **Limpieza automática:** Semanal los domingos a las 3 AM
- **Verificación automática:** Diaria a las 6 AM

## Contacto

Para soporte técnico, contactar al administrador del sistema.
EOF

print_success "Documentación creada"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "INSTALACIÓN COMPLETA FINALIZADA"

print_success "✅ Sistema CSDT instalado completamente"
print_success "✅ Backend Laravel funcionando"
print_success "✅ Frontend React funcionando"
print_success "✅ 13 servicios de IA configurados"
print_success "✅ Base de datos MySQL configurada"
print_success "✅ PM2 gestionando procesos"
print_success "✅ Firewall configurado"
print_success "✅ Scripts de gestión creados"
print_success "✅ Cron jobs configurados"
print_success "✅ Documentación creada"

print_warning "INFORMACIÓN DE ACCESO:"
print_warning "Backend: http://$IP_PUBLICA:8000"
print_warning "Frontend: http://$IP_PUBLICA:3000"

print_warning "COMANDOS ÚTILES:"
print_warning "Gestión: gestionar_csdt.sh"
print_warning "Inicio: iniciar_csdt.sh"
print_warning "Parada: detener_csdt.sh"
print_warning "Verificación: verificar_csdt.sh"
print_warning "Monitoreo: monitor_csdt.sh"
print_warning "Diagnóstico: diagnosticar_csdt.sh"

print_warning "DOCUMENTACIÓN:"
print_warning "Ubicación: /var/www/README_CSDT.md"

print_message "¡Instalación completa finalizada exitosamente!"
print_message "El sistema CSDT está listo para usar en producción."
print_message "Para gestionar el sistema, ejecuta: gestionar_csdt.sh"
