#!/bin/bash

# ===========================================
# SCRIPT DE LIMPIEZA DE GUÍAS ANTIGUAS
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

print_header "LIMPIANDO GUÍAS ANTIGUAS Y ORGANIZANDO DOCUMENTACIÓN"

# Crear directorio de respaldo
print_message "Creando directorio de respaldo..."
mkdir -p guias_respaldo_$(date +%Y%m%d)

# Mover guías antiguas al respaldo
print_message "Moviendo guías antiguas al respaldo..."

# Guías que se van a respaldar
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
)

for guia in "${guias_antiguas[@]}"; do
    if [ -f "$guia" ]; then
        print_message "Respaldo: $guia"
        mv "$guia" "guias_respaldo_$(date +%Y%m%d)/"
    fi
done

# Mover archivos de configuración antiguos
print_message "Respaldo de archivos de configuración antiguos..."
if [ -f "instalar_digitalocean.sh" ]; then
    mv "instalar_digitalocean.sh" "guias_respaldo_$(date +%Y%m%d)/"
fi

if [ -f "script_despliegue_automatizado.sh" ]; then
    mv "script_despliegue_automatizado.sh" "guias_respaldo_$(date +%Y%m%d)/"
fi

# Crear estructura de documentación organizada
print_message "Creando estructura de documentación organizada..."
mkdir -p documentacion/{instalacion,configuracion,desarrollo,produccion}

# Mover archivos de documentación
print_message "Organizando documentación..."

# Documentación de instalación
if [ -f "GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_FINAL.md" ]; then
    mv "GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_FINAL.md" "documentacion/instalacion/"
fi

if [ -f "instalar_csdt_digitalocean.sh" ]; then
    mv "instalar_csdt_digitalocean.sh" "documentacion/instalacion/"
fi

if [ -f "verificar_instalacion_csdt.sh" ]; then
    mv "verificar_instalacion_csdt.sh" "documentacion/instalacion/"
fi

# Documentación de configuración
if [ -f "CONFIGURACIONES_ENV_PRODUCCION.md" ]; then
    mv "CONFIGURACIONES_ENV_PRODUCCION.md" "documentacion/configuracion/"
fi

# Documentación de desarrollo
if [ -f "MEJORAS_PROYECTO_LOCAL.md" ]; then
    mv "MEJORAS_PROYECTO_LOCAL.md" "documentacion/desarrollo/"
fi

if [ -f "MEJORAS_IA_PROFESIONALES.txt" ]; then
    mv "MEJORAS_IA_PROFESIONALES.txt" "documentacion/desarrollo/"
fi

# Documentación de producción
if [ -f "RESUMEN_EJECUTIVO_INSTALACION.md" ]; then
    mv "RESUMEN_EJECUTIVO_INSTALACION.md" "documentacion/produccion/"
fi

# Crear README principal
print_message "Creando README principal..."
cat > README.md << 'EOF'
# 🚀 CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL (CSDT)

## 📋 Descripción del Proyecto

Sistema integral de veeduría ciudadana y desarrollo territorial con servicios de inteligencia artificial especializados para análisis jurídico y constitucional.

## 🏗️ Arquitectura del Sistema

- **Backend:** Laravel 12 + Inertia.js + Vite
- **Frontend:** React 18 + Vite + TailwindCSS
- **Base de Datos:** MySQL 8.0 (Producción) / SQLite (Desarrollo)
- **Servicios de IA:** 8 servicios especializados en JavaScript
- **Gestión de Procesos:** PM2
- **Servidor:** Ubuntu 22.04 LTS

## 📁 Estructura del Proyecto

```
final/
├── backend-csdt/                    # Backend Laravel
│   ├── app/                        # Aplicación Laravel
│   ├── database/                   # Migraciones y Seeders
│   ├── resources/                  # Recursos (JS, CSS, Views)
│   ├── routes/                     # Rutas de la API
│   └── vendor/                     # Dependencias PHP
├── frontend-csdt-final/            # Frontend React
│   ├── src/                       # Código fuente React
│   │   ├── services/              # Servicios de IA
│   │   ├── components/            # Componentes React
│   │   └── paginas/               # Páginas de la aplicación
│   └── dist/                      # Archivos compilados
└── documentacion/                  # Documentación organizada
    ├── instalacion/               # Guías de instalación
    ├── configuracion/             # Configuraciones
    ├── desarrollo/                # Documentación de desarrollo
    └── produccion/                # Documentación de producción
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
sudo ./documentacion/instalacion/instalar_csdt_digitalocean.sh

# Verificar instalación
sudo ./documentacion/instalacion/verificar_instalacion_csdt.sh
```

## 🔧 Servicios de IA Implementados

1. **IAMejoradaService.js** - Análisis general mejorado
2. **IAsProfesionalesService.js** - Análisis profesional especializado
3. **SistemaIAProfesionalService.js** - Sistema profesional completo
4. **ChatGPTMejoradoService.js** - Chat mejorado con IA
5. **IAsTecnicasService.js** - Análisis técnico especializado
6. **ConsejoIAService.js** - Servicio de consejo con IA
7. **AnalisisJuridicoService.js** - Análisis jurídico especializado
8. **AnalisisNarrativoProfesionalService.js** - Análisis narrativo profesional

## 📚 Documentación

- [Guía de Instalación Completa](documentacion/instalacion/GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_FINAL.md)
- [Script de Instalación Automatizada](documentacion/instalacion/instalar_csdt_digitalocean.sh)
- [Script de Verificación](documentacion/instalacion/verificar_instalacion_csdt.sh)
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

## 📞 Soporte

Para soporte técnico o consultas sobre el proyecto, contactar al equipo de desarrollo.

---

**Versión:** 1.0.0  
**Última actualización:** $(date +%Y-%m-%d)  
**Estado:** Producción
EOF

# Crear archivo de índice de documentación
print_message "Creando índice de documentación..."
cat > documentacion/README.md << 'EOF'
# 📚 Documentación CSDT

## 📁 Estructura de Documentación

### 🚀 Instalación
- `GUIA_INSTALACION_DIGITALOCEAN_COMPLETA_FINAL.md` - Guía completa de instalación
- `instalar_csdt_digitalocean.sh` - Script de instalación automatizada
- `verificar_instalacion_csdt.sh` - Script de verificación y diagnóstico

### ⚙️ Configuración
- `CONFIGURACIONES_ENV_PRODUCCION.md` - Configuraciones de producción

### 💻 Desarrollo
- `MEJORAS_PROYECTO_LOCAL.md` - Mejoras implementadas en el proyecto local
- `MEJORAS_IA_PROFESIONALES.txt` - Mejoras en servicios de IA

### 🏭 Producción
- `RESUMEN_EJECUTIVO_INSTALACION.md` - Resumen ejecutivo de instalación

## 🔄 Guías Antiguas (Respaldo)

Las guías antiguas han sido movidas al directorio `guias_respaldo_YYYYMMDD/` para mantener un historial pero evitar confusión.

## 📝 Notas

- Todas las guías están actualizadas para la versión actual del proyecto
- Los scripts de instalación incluyen verificación automática
- La documentación sigue las convenciones del proyecto (PascalCase en español)
EOF

print_header "LIMPIEZA COMPLETADA"

print_message "✅ Guías antiguas respaldadas en: guias_respaldo_$(date +%Y%m%d)/"
print_message "✅ Documentación organizada en: documentacion/"
print_message "✅ README principal creado"
print_message "✅ Índice de documentación creado"

print_warning "IMPORTANTE:"
print_warning "1. Revisa el directorio de respaldo antes de eliminar archivos"
print_warning "2. La documentación está ahora organizada por categorías"
print_warning "3. Usa la guía de instalación final para nuevas instalaciones"

print_message "¡Documentación organizada exitosamente!"
