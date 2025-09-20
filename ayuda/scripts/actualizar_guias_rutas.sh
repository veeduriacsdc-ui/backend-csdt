#!/bin/bash

# ===========================================
# SCRIPT PARA ACTUALIZAR GUÍAS CON NUEVAS RUTAS
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

print_header "ACTUALIZANDO GUÍAS CON NUEVAS RUTAS"

# ===========================================
# CREAR GUÍA ACTUALIZADA CON RUTAS CORRECTAS
# ===========================================
print_message "Creando guía actualizada con rutas correctas..."

cat > GUIA_INSTALACION_DIGITALOCEAN_ACTUALIZADA.md << 'EOF'
# 🚀 GUÍA DE INSTALACIÓN CSDT EN DIGITALOCEAN UBUNTU - RUTAS ACTUALIZADAS

## 📋 INFORMACIÓN DEL SERVIDOR

- **IP Pública:** `64.225.113.49`
- **IP Privada:** `10.120.0.2`
- **Repositorio Frontend:** `https://github.com/veeduriacsdc-ui/frontend-csdt`
- **Repositorio Backend:** `https://github.com/veeduriacsdc-ui/backend-csdt.git`
- **Base de Datos:** MySQL 8.0 (Producción)
- **Acceso Frontend:** `http://64.225.113.49:3000`
- **Acceso API:** `http://64.225.113.49:8000`

## 🏗️ ESTRUCTURA DEL PROYECTO EN DIGITALOCEAN

```
/var/www/
├── backend-csdt/                    # Backend Laravel
│   ├── ecosystem.config.js         # Configuración PM2 del backend
│   ├── ayuda/                      # Scripts de gestión del backend
│   │   ├── iniciar_backend.sh
│   │   ├── detener_backend.sh
│   │   ├── reiniciar_backend.sh
│   │   └── verificar_backend.sh
│   ├── artisan
│   ├── .env
│   └── ...
└── frontend-csdt/                  # Frontend React
    ├── ecosystem-frontend.config.js # Configuración PM2 del frontend
    ├── ayuda/                      # Scripts de gestión del frontend
    │   ├── iniciar_frontend.sh
    │   ├── detener_frontend.sh
    │   ├── reiniciar_frontend.sh
    │   ├── compilar_frontend.sh
    │   ├── verificar_frontend.sh
    │   └── desarrollo_frontend.sh
    ├── package.json
    ├── .env
    └── ...
```

## 🚀 INSTALACIÓN AUTOMÁTICA

### **Opción 1: Instalación Completa**

```bash
# 1. Conectar al servidor
ssh root@64.225.113.49

# 2. Ejecutar instalación completa
sudo ./instalar_csdt_completo.sh
```

### **Opción 2: Crear Archivos Ecosystem**

```bash
# Crear archivos ecosystem.config.js
sudo ./scripts/crear_ecosystem_completo.sh
```

## 🔧 GESTIÓN DE SERVICIOS

### **Comandos Principales:**

```bash
# Gestión completa (menú interactivo)
/usr/local/bin/gestionar_csdt.sh

# Inicio rápido
/usr/local/bin/iniciar_csdt.sh

# Parada rápida
/usr/local/bin/detener_csdt.sh
```

### **Comandos Específicos del Backend:**

```bash
# Navegar al backend
cd /var/www/backend-csdt

# Iniciar backend
./ayuda/iniciar_backend.sh

# Detener backend
./ayuda/detener_backend.sh

# Reiniciar backend
./ayuda/reiniciar_backend.sh

# Verificar backend
./ayuda/verificar_backend.sh
```

### **Comandos Específicos del Frontend:**

```bash
# Navegar al frontend
cd /var/www/frontend-csdt

# Iniciar frontend
./ayuda/iniciar_frontend.sh

# Detener frontend
./ayuda/detener_frontend.sh

# Reiniciar frontend
./ayuda/reiniciar_frontend.sh

# Compilar frontend
./ayuda/compilar_frontend.sh

# Verificar frontend
./ayuda/verificar_frontend.sh

# Modo desarrollo
./ayuda/desarrollo_frontend.sh
```

## 📁 ARCHIVOS ECOSYSTEM.CONFIG.JS

### **Backend (ecosystem.config.js):**

```javascript
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
        env_file: '/var/www/backend-csdt/.env'
    }]
};
```

### **Frontend (ecosystem-frontend.config.js):**

```javascript
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
        env_file: '/var/www/frontend-csdt/.env'
    }]
};
```

## 🎯 CARACTERÍSTICAS DE LOS ARCHIVOS ECOSYSTEM

### **Configuraciones Incluidas:**

- ✅ **Rutas absolutas:** Configuradas para funcionar en DigitalOcean
- ✅ **Carpeta ayuda:** Incluida en ignore_watch para evitar reinicios
- ✅ **Logs organizados:** Directorios específicos para cada servicio
- ✅ **Variables de entorno:** Archivos .env específicos
- ✅ **Reinicio automático:** Configurado para manejar errores
- ✅ **Gestión de memoria:** Límites configurados
- ✅ **Modo producción:** Configurado para producción

### **Scripts de Gestión Incluidos:**

#### **Backend:**
- `iniciar_backend.sh` - Inicia el backend
- `detener_backend.sh` - Detiene el backend
- `reiniciar_backend.sh` - Reinicia el backend
- `verificar_backend.sh` - Verifica el estado del backend

#### **Frontend:**
- `iniciar_frontend.sh` - Inicia el frontend
- `detener_frontend.sh` - Detiene el frontend
- `reiniciar_frontend.sh` - Reinicia el frontend
- `compilar_frontend.sh` - Compila el frontend
- `verificar_frontend.sh` - Verifica el estado del frontend
- `desarrollo_frontend.sh` - Modo desarrollo

## 🚨 SOLUCIÓN DE PROBLEMAS

### **Problemas Comunes:**

#### **1. Error de Permisos:**
```bash
# Corregir permisos
sudo chown -R www-data:www-data /var/www/
sudo chmod -R 755 /var/www/
sudo chmod -R 755 /var/www/*/ayuda
```

#### **2. Error de Archivos Ecosystem:**
```bash
# Recrear archivos ecosystem
sudo ./scripts/crear_ecosystem_completo.sh
```

#### **3. Error de PM2:**
```bash
# Reiniciar PM2
pm2 delete all
/usr/local/bin/iniciar_csdt.sh
```

#### **4. Error de Frontend:**
```bash
# Compilar frontend
cd /var/www/frontend-csdt
./ayuda/compilar_frontend.sh
./ayuda/reiniciar_frontend.sh
```

## 📊 MONITOREO

### **Comandos de Monitoreo:**

```bash
# Estado de servicios
pm2 status

# Logs en tiempo real
pm2 logs

# Logs específicos
pm2 logs backend-csdt
pm2 logs frontend-csdt

# Verificación completa
/usr/local/bin/gestionar_csdt.sh
```

### **URLs de Acceso:**
- **Frontend:** `http://64.225.113.49:3000`
- **API Backend:** `http://64.225.113.49:8000`

## 🎉 ¡INSTALACIÓN COMPLETADA!

Con esta configuración tienes:

- ✅ **Archivos ecosystem.config.js** configurados correctamente
- ✅ **Carpetas ayuda** con scripts de gestión
- ✅ **Rutas absolutas** para funcionar en DigitalOcean
- ✅ **Scripts de gestión** completos
- ✅ **Monitoreo** del sistema incluido
- ✅ **Solución de problemas** automatizada

**¡El sistema CSDT está listo para usar en producción!** 🚀
EOF

print_success "✅ Guía actualizada creada"

# ===========================================
# CREAR SCRIPT DE VERIFICACIÓN DE RUTAS
# ===========================================
print_message "Creando script de verificación de rutas..."

cat > /usr/local/bin/verificar_rutas_csdt.sh << 'EOF'
#!/bin/bash
# Script para verificar rutas de CSDT

echo "=== VERIFICACIÓN DE RUTAS CSDT ==="
echo "Fecha: $(date)"
echo ""

# Verificar estructura del backend
echo "=== ESTRUCTURA BACKEND ==="
if [ -d "/var/www/backend-csdt" ]; then
    echo "✅ Directorio backend existe"
    
    if [ -f "/var/www/backend-csdt/ecosystem.config.js" ]; then
        echo "✅ ecosystem.config.js existe"
    else
        echo "❌ ecosystem.config.js no existe"
    fi
    
    if [ -d "/var/www/backend-csdt/ayuda" ]; then
        echo "✅ Carpeta ayuda existe"
        echo "Scripts en ayuda: $(ls /var/www/backend-csdt/ayuda/ | wc -l)"
    else
        echo "❌ Carpeta ayuda no existe"
    fi
else
    echo "❌ Directorio backend no existe"
fi

echo ""

# Verificar estructura del frontend
echo "=== ESTRUCTURA FRONTEND ==="
if [ -d "/var/www/frontend-csdt" ]; then
    echo "✅ Directorio frontend existe"
    
    if [ -f "/var/www/frontend-csdt/ecosystem-frontend.config.js" ]; then
        echo "✅ ecosystem-frontend.config.js existe"
    else
        echo "❌ ecosystem-frontend.config.js no existe"
    fi
    
    if [ -d "/var/www/frontend-csdt/ayuda" ]; then
        echo "✅ Carpeta ayuda existe"
        echo "Scripts en ayuda: $(ls /var/www/frontend-csdt/ayuda/ | wc -l)"
    else
        echo "❌ Carpeta ayuda no existe"
    fi
else
    echo "❌ Directorio frontend no existe"
fi

echo ""

# Verificar scripts globales
echo "=== SCRIPTS GLOBALES ==="
[ -f "/usr/local/bin/gestionar_csdt.sh" ] && echo "✅ gestionar_csdt.sh" || echo "❌ gestionar_csdt.sh"
[ -f "/usr/local/bin/iniciar_csdt.sh" ] && echo "✅ iniciar_csdt.sh" || echo "❌ iniciar_csdt.sh"
[ -f "/usr/local/bin/detener_csdt.sh" ] && echo "✅ detener_csdt.sh" || echo "❌ detener_csdt.sh"

echo ""
echo "Verificación completada"
EOF

chmod +x /usr/local/bin/verificar_rutas_csdt.sh

print_success "✅ Script de verificación de rutas creado"

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "GUÍAS ACTUALIZADAS CON NUEVAS RUTAS"

print_success "✅ Guía actualizada creada"
print_success "✅ Script de verificación de rutas creado"
print_success "✅ Rutas configuradas para DigitalOcean"

print_warning "ARCHIVOS CREADOS:"
print_warning "Guía actualizada: GUIA_INSTALACION_DIGITALOCEAN_ACTUALIZADA.md"
print_warning "Script de verificación: /usr/local/bin/verificar_rutas_csdt.sh"

print_warning "COMANDOS ÚTILES:"
print_warning "Verificar rutas: /usr/local/bin/verificar_rutas_csdt.sh"
print_warning "Gestión completa: /usr/local/bin/gestionar_csdt.sh"

print_message "¡Guías actualizadas con rutas correctas!"
