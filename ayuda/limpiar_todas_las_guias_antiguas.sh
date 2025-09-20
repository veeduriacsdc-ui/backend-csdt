#!/bin/bash

# ===========================================
# SCRIPT DE LIMPIEZA COMPLETA DE GUÍAS ANTIGUAS
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

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

print_header "LIMPIANDO TODAS LAS GUÍAS ANTIGUAS Y ORGANIZANDO DOCUMENTACIÓN"

# Crear directorio de respaldo con timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="guias_respaldo_${TIMESTAMP}"

print_step "Creando directorio de respaldo: $BACKUP_DIR"
mkdir -p "$BACKUP_DIR"

# Lista de todas las guías antiguas a respaldar
guias_antiguas=(
    "GUIA_INSTALACION_DIGITALOCEAN_SIN_DOMINIO.md"
    "GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_MEJORADA.md"
    "GUIA_INSTALACION_DIGITALOCEAN_CORREGIDA.md"
    "GUIA_INSTALACION_DIGITALOCEAN_SIN_DOMINIO_CORREGIDA.md"
    "GUIA_DESPLIEGUE_DIGITALOCEAN.md"
    "GUIA_DIGITALOCEAN_COPY_PASTE.md"
    "GUIA_INSTALACION_DIGITALOCEAN_MEJORADA.md"
    "GUIA_INSTALACION_DIGITALOCEAN.md"
    "GUIA_INSTALACION_IA_DIGITALOCEAN.md"
    "GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_FINAL.md"
    "RESUMEN_MEJORAS_IMPLEMENTADAS.md"
)

# Mover guías antiguas al respaldo
print_step "Respaldo de guías antiguas..."
for guia in "${guias_antiguas[@]}"; do
    if [ -f "$guia" ]; then
        print_message "Respaldo: $guia"
        mv "$guia" "$BACKUP_DIR/"
    else
        print_warning "No encontrado: $guia"
    fi
done

# Mover archivos de configuración antiguos
print_step "Respaldo de archivos de configuración antiguos..."
archivos_config=(
    "instalar_digitalocean.sh"
    "script_despliegue_automatizado.sh"
    "instalar_csdt_digitalocean.sh"
    "verificar_instalacion_csdt.sh"
    "limpiar_guias_antiguas.sh"
)

for archivo in "${archivos_config[@]}"; do
    if [ -f "$archivo" ]; then
        print_message "Respaldo: $archivo"
        mv "$archivo" "$BACKUP_DIR/"
    fi
done

# Crear estructura de documentación organizada
print_step "Creando estructura de documentación organizada..."
mkdir -p documentacion/{instalacion,configuracion,desarrollo,produccion,scripts}

# Mover archivos de documentación
print_step "Organizando documentación..."

# Documentación de instalación
if [ -f "GUIA_INSTALACION_DIGITALOCEAN_DEFINITIVA.md" ]; then
    mv "GUIA_INSTALACION_DIGITALOCEAN_DEFINITIVA.md" "documentacion/instalacion/"
    print_success "Guía definitiva movida a documentacion/instalacion/"
fi

if [ -f "instalar_csdt_digitalocean_completo.sh" ]; then
    mv "instalar_csdt_digitalocean_completo.sh" "documentacion/scripts/"
    print_success "Script de instalación movido a documentacion/scripts/"
fi

# Documentación de configuración
if [ -f "CONFIGURACIONES_ENV_PRODUCCION.md" ]; then
    mv "CONFIGURACIONES_ENV_PRODUCCION.md" "documentacion/configuracion/"
    print_success "Configuraciones movidas a documentacion/configuracion/"
fi

# Documentación de desarrollo
if [ -f "MEJORAS_PROYECTO_LOCAL.md" ]; then
    mv "MEJORAS_PROYECTO_LOCAL.md" "documentacion/desarrollo/"
    print_success "Mejoras de desarrollo movidas a documentacion/desarrollo/"
fi

if [ -f "MEJORAS_IA_PROFESIONALES.txt" ]; then
    mv "MEJORAS_IA_PROFESIONALES.txt" "documentacion/desarrollo/"
    print_success "Mejoras de IA movidas a documentacion/desarrollo/"
fi

# Documentación de producción
if [ -f "RESUMEN_EJECUTIVO_INSTALACION.md" ]; then
    mv "RESUMEN_EJECUTIVO_INSTALACION.md" "documentacion/produccion/"
    print_success "Resumen ejecutivo movido a documentacion/produccion/"
fi

# Crear README principal actualizado
print_step "Creando README principal actualizado..."
cat > README.md << 'EOF'
# 🚀 CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL (CSDT)

## 📋 Descripción del Proyecto

Sistema integral de veeduría ciudadana y desarrollo territorial con servicios de inteligencia artificial especializados para análisis jurídico y constitucional.

## 🏗️ Arquitectura del Sistema

- **Backend:** Laravel 12 + Inertia.js + Vite
- **Frontend:** React 18 + Vite + TailwindCSS
- **Base de Datos:** MySQL 8.0 (Producción) / SQLite (Desarrollo)
- **Servicios de IA:** 13 servicios especializados en JavaScript
- **Gestión de Procesos:** PM2
- **Servidor:** Ubuntu 22.04 LTS

## 📁 Estructura del Proyecto

```
final/
├── backend-csdt/                    # Backend Laravel
│   ├── app/                        # Aplicación Laravel
│   ├── database/                   # Migraciones y Seeders
│   │   ├── migrations/             # 19 migraciones
│   │   └── seeders/                # 5 seeders
│   ├── resources/                  # Recursos (JS, CSS, Views)
│   ├── routes/                     # Rutas de la API
│   ├── vendor/                     # 23 dependencias PHP
│   └── node_modules/               # 38 dependencias Node.js
├── frontend-csdt-final/            # Frontend React
│   ├── src/                       # Código fuente React
│   │   ├── services/              # 13 Servicios de IA
│   │   ├── components/            # Componentes React
│   │   └── paginas/               # Páginas de la aplicación
│   ├── dist/                      # Archivos compilados
│   └── node_modules/              # 13 dependencias principales
└── documentacion/                  # Documentación organizada
    ├── instalacion/               # Guías de instalación
    ├── configuracion/             # Configuraciones
    ├── desarrollo/                # Documentación de desarrollo
    ├── produccion/                # Documentación de producción
    └── scripts/                   # Scripts de automatización
```

## 🚀 Instalación Rápida

### Para Desarrollo Local:
```bash
# Backend
cd backend-csdt
composer install
npm install
php artisan serve

# Frontend
cd frontend-csdt-final
npm install
npm run dev
```

### Para Producción (DigitalOcean):
```bash
# Ejecutar script de instalación automatizada
sudo ./documentacion/scripts/instalar_csdt_digitalocean_completo.sh
```

## 🔧 Servicios de IA Implementados (13 servicios)

1. **IAMejoradaService.js** - Análisis general mejorado
2. **IAsProfesionalesService.js** - Análisis profesional especializado
3. **SistemaIAProfesionalService.js** - Sistema profesional completo
4. **ChatGPTMejoradoService.js** - Chat mejorado con IA
5. **IAsTecnicasService.js** - Análisis técnico especializado
6. **ConsejoIAService.js** - Servicio de consejo con IA
7. **AnalisisJuridicoService.js** - Análisis jurídico especializado
8. **AnalisisNarrativoProfesionalService.js** - Análisis narrativo profesional
9. **ConsejoVeeduriaTerritorialService.js** - Servicio territorial
10. **api.js** - Servicio de API
11. **authService.js** - Servicio de autenticación
12. **configuracion.js** - Servicio de configuración
13. **registroService.js** - Servicio de registro

## 📚 Documentación

- [Guía de Instalación Definitiva](documentacion/instalacion/GUIA_INSTALACION_DIGITALOCEAN_DEFINITIVA.md)
- [Script de Instalación Automatizada](documentacion/scripts/instalar_csdt_digitalocean_completo.sh)
- [Configuraciones de Producción](documentacion/configuracion/)

## 🌐 URLs de Acceso

- **Desarrollo Local:**
  - Frontend: http://localhost:5173
  - API: http://localhost:8000

- **Producción (DigitalOcean):**
  - Frontend: http://64.225.113.49:3000
  - API: http://64.225.113.49:8000

## 🛠️ Comandos Útiles

### Desarrollo:
```bash
# Backend
php artisan serve
php artisan migrate
php artisan db:seed

# Frontend
npm run dev
npm run build
```

### Producción:
```bash
# Ver estado de servicios
pm2 status

# Reiniciar servicios
pm2 restart all

# Ver logs
pm2 logs
```

## 📊 Estadísticas del Proyecto

- **Backend Dependencias:** 23 paquetes PHP + 38 paquetes Node.js
- **Frontend Dependencias:** 13 paquetes principales
- **Servicios de IA:** 13 servicios especializados
- **Migraciones:** 19 migraciones de base de datos
- **Seeders:** 5 seeders de datos
- **Líneas de Código:** 1000+ líneas en servicios de IA

## 📞 Soporte

Para soporte técnico o consultas sobre el proyecto, contactar al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Última actualización:** $(date +%Y-%m-%d)  
**Estado:** Producción
EOF

# Crear archivo de índice de documentación
print_step "Creando índice de documentación..."
cat > documentacion/README.md << 'EOF'
# 📚 Documentación CSDT

## 📁 Estructura de Documentación

### 🚀 Instalación
- `GUIA_INSTALACION_DIGITALOCEAN_DEFINITIVA.md` - Guía definitiva de instalación
- `instalar_csdt_digitalocean_completo.sh` - Script de instalación automatizada

### ⚙️ Configuración
- `CONFIGURACIONES_ENV_PRODUCCION.md` - Configuraciones de producción

### 💻 Desarrollo
- `MEJORAS_PROYECTO_LOCAL.md` - Mejoras implementadas en el proyecto local
- `MEJORAS_IA_PROFESIONALES.txt` - Mejoras en servicios de IA

### 🏭 Producción
- `RESUMEN_EJECUTIVO_INSTALACION.md` - Resumen ejecutivo de instalación

### 🔧 Scripts
- `instalar_csdt_digitalocean_completo.sh` - Script de instalación automatizada

## 🔄 Guías Antiguas (Respaldo)

Las guías antiguas han sido movidas al directorio `guias_respaldo_YYYYMMDD_HHMMSS/` para mantener un historial pero evitar confusión.

## 📝 Notas

- Todas las guías están actualizadas para la versión actual del proyecto
- Los scripts de instalación incluyen verificación automática
- La documentación sigue las convenciones del proyecto (PascalCase en español)
- Se han eliminado todas las guías duplicadas y obsoletas
EOF

# Crear script de verificación
print_step "Creando script de verificación..."
cat > verificar_instalacion_csdt.sh << 'EOF'
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

IP_PUBLICA="64.225.113.49"

print_header "VERIFICACIÓN Y DIAGNÓSTICO CSDT"

# Verificar servicios del sistema
print_header "VERIFICANDO SERVICIOS DEL SISTEMA"

services=("php" "composer" "node" "npm" "git" "mysql" "pm2")
for service in "${services[@]}"; do
    if command -v $service &> /dev/null; then
        print_message "✅ $service está instalado"
    else
        print_error "❌ $service no está instalado"
    fi
done

# Verificar estado de PM2
print_header "VERIFICANDO ESTADO DE PM2"
pm2 status

# Verificar conectividad
print_header "VERIFICANDO CONECTIVIDAD"
curl -I http://localhost:8000 && print_message "✅ API local responde" || print_warning "⚠️ API local no responde"
curl -I http://localhost:3000 && print_message "✅ Frontend local responde" || print_warning "⚠️ Frontend local no responde"
curl -I http://$IP_PUBLICA:8000 && print_message "✅ API externa responde" || print_warning "⚠️ API externa no responde"
curl -I http://$IP_PUBLICA:3000 && print_message "✅ Frontend externo responde" || print_warning "⚠️ Frontend externo no responde"

print_header "VERIFICACIÓN COMPLETADA"
EOF

chmod +x verificar_instalacion_csdt.sh
mv verificar_instalacion_csdt.sh documentacion/scripts/

print_success "Script de verificación creado y movido a documentacion/scripts/"

# Crear script de mantenimiento
print_step "Creando script de mantenimiento..."
cat > documentacion/scripts/mantenimiento_csdt.sh << 'EOF'
#!/bin/bash

# ===========================================
# SCRIPT DE MANTENIMIENTO CSDT
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

print_message() {
    echo -e "\033[0;32m[INFO]\033[0m $1"
}

print_header() {
    echo -e "\033[0;34m===========================================\033[0m"
    echo -e "\033[0;34m$1\033[0m"
    echo -e "\033[0;34m===========================================\033[0m"
}

print_header "MANTENIMIENTO CSDT"

# Actualizar frontend
print_message "Actualizando frontend..."
cd /var/www/frontend-csdt
git pull origin main
npm install
npm run build
pm2 restart frontend-csdt

# Actualizar backend
print_message "Actualizando backend..."
cd /var/www/backend-csdt
git pull origin main
composer install --optimize-autoloader --no-dev
npm install
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
pm2 restart backend-csdt

print_message "Mantenimiento completado"
EOF

chmod +x documentacion/scripts/mantenimiento_csdt.sh

print_success "Script de mantenimiento creado"

# Mostrar resumen final
print_header "LIMPIEZA COMPLETADA"

print_success "✅ Guías antiguas respaldadas en: $BACKUP_DIR/"
print_success "✅ Documentación organizada en: documentacion/"
print_success "✅ README principal actualizado"
print_success "✅ Índice de documentación creado"
print_success "✅ Scripts de verificación y mantenimiento creados"

echo ""
print_warning "IMPORTANTE:"
print_warning "1. Revisa el directorio de respaldo antes de eliminar archivos"
print_warning "2. La documentación está ahora organizada por categorías"
print_warning "3. Usa la guía definitiva para nuevas instalaciones"
print_warning "4. Los scripts están en documentacion/scripts/"

echo ""
print_message "Estructura final:"
echo "├── documentacion/"
echo "│   ├── instalacion/"
echo "│   │   └── GUIA_INSTALACION_DIGITALOCEAN_DEFINITIVA.md"
echo "│   ├── scripts/"
echo "│   │   ├── instalar_csdt_digitalocean_completo.sh"
echo "│   │   ├── verificar_instalacion_csdt.sh"
echo "│   │   └── mantenimiento_csdt.sh"
echo "│   ├── configuracion/"
echo "│   ├── desarrollo/"
echo "│   └── produccion/"
echo "└── guias_respaldo_${TIMESTAMP}/"

echo ""
print_success "¡Documentación organizada exitosamente!"
