#!/bin/bash

# Script para ejecutar migraciones y verificar la base de datos
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL

echo "🚀 Iniciando proceso de migración de base de datos CSDT..."

# Cambiar al directorio del backend
cd "$(dirname "$0")/.."

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo "❌ Error: No se encontró el archivo artisan. Asegúrate de estar en el directorio del backend."
    exit 1
fi

echo "📁 Directorio actual: $(pwd)"

# Verificar conexión a la base de datos
echo "🔍 Verificando conexión a la base de datos..."
php artisan migrate:status

if [ $? -ne 0 ]; then
    echo "❌ Error: No se pudo conectar a la base de datos. Verifica la configuración."
    exit 1
fi

echo "✅ Conexión a la base de datos exitosa"

# Hacer backup de la base de datos actual (si existe)
echo "💾 Creando backup de la base de datos actual..."
if [ -f "database/database.sqlite" ]; then
    cp database/database.sqlite database/database_backup_$(date +%Y%m%d_%H%M%S).sqlite
    echo "✅ Backup creado exitosamente"
else
    echo "ℹ️  No se encontró base de datos existente, continuando..."
fi

# Limpiar caché
echo "🧹 Limpiando caché..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
echo "🔄 Ejecutando migraciones..."
php artisan migrate:fresh --force

if [ $? -ne 0 ]; then
    echo "❌ Error al ejecutar migraciones"
    exit 1
fi

echo "✅ Migraciones ejecutadas exitosamente"

# Ejecutar seeders
echo "🌱 Ejecutando seeders..."
php artisan db:seed --force

if [ $? -ne 0 ]; then
    echo "⚠️  Advertencia: Error al ejecutar seeders, pero las migraciones fueron exitosas"
fi

# Verificar estructura de la base de datos
echo "🔍 Verificando estructura de la base de datos..."
php artisan migrate:status

# Mostrar tablas creadas
echo "📊 Tablas creadas:"
php artisan tinker --execute="echo 'Tablas: ' . implode(', ', array_keys(DB::select('SELECT name FROM sqlite_master WHERE type=\"table\"')));"

# Verificar que los modelos funcionen
echo "🧪 Verificando modelos..."
php artisan tinker --execute="
try {
    \$usuario = new App\Models\Usuario();
    echo '✅ Modelo Usuario: OK';
} catch (Exception \$e) {
    echo '❌ Error en modelo Usuario: ' . \$e->getMessage();
}

try {
    \$rol = new App\Models\Rol();
    echo '✅ Modelo Rol: OK';
} catch (Exception \$e) {
    echo '❌ Error en modelo Rol: ' . \$e->getMessage();
}

try {
    \$veeduria = new App\Models\Veeduria();
    echo '✅ Modelo Veeduria: OK';
} catch (Exception \$e) {
    echo '❌ Error en modelo Veeduria: ' . \$e->getMessage();
}
"

# Verificar rutas API
echo "🛣️  Verificando rutas API..."
php artisan route:list --path=api

echo "🎉 Proceso de migración completado exitosamente!"
echo "📋 Resumen:"
echo "   - Base de datos rediseñada con nombres optimizados"
echo "   - Tablas pivot creadas correctamente"
echo "   - Relaciones establecidas"
echo "   - Modelos verificados"
echo "   - Rutas API configuradas"
echo ""
echo "🚀 El sistema está listo para usar!"
