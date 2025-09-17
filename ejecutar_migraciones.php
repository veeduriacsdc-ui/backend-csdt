<?php
/**
 * Script para ejecutar migraciones y verificar configuración de base de datos
 * CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
 */

echo "=== CONFIGURACIÓN Y MIGRACIÓN DE BASE DE DATOS CSDT ===\n\n";

// Verificar si existe el archivo .env
if (!file_exists('.env')) {
    echo "❌ ERROR: No se encontró el archivo .env\n";
    echo "📝 Creando archivo .env desde .env.example...\n";
    
    if (file_exists('.env.example')) {
        copy('.env.example', '.env');
        echo "✅ Archivo .env creado exitosamente\n";
    } else {
        echo "❌ ERROR: No se encontró .env.example\n";
        echo "📝 Creando archivo .env básico...\n";
        
        $envContent = 'APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Bogota
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_veeduria
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database';
        
        file_put_contents('.env', $envContent);
        echo "✅ Archivo .env básico creado\n";
    }
}

// Verificar conexión a base de datos
echo "\n🔍 Verificando conexión a base de datos...\n";

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Conexión a MySQL exitosa\n";
    
    // Verificar si existe la base de datos
    $stmt = $pdo->query("SHOW DATABASES LIKE 'csdt_veeduria'");
    if ($stmt->rowCount() == 0) {
        echo "📝 Creando base de datos 'csdt_veeduria'...\n";
        $pdo->exec("CREATE DATABASE csdt_veeduria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Base de datos creada exitosamente\n";
    } else {
        echo "✅ Base de datos 'csdt_veeduria' ya existe\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR de conexión: " . $e->getMessage() . "\n";
    echo "💡 Asegúrate de que XAMPP esté ejecutándose y MySQL esté activo\n";
    exit(1);
}

echo "\n🚀 Ejecutando comandos de Laravel...\n";

// Lista de comandos a ejecutar
$comandos = [
    'php artisan key:generate' => 'Generando clave de aplicación',
    'php artisan config:clear' => 'Limpiando configuración',
    'php artisan cache:clear' => 'Limpiando caché',
    'php artisan migrate:fresh --seed' => 'Ejecutando migraciones y seeders'
];

foreach ($comandos as $comando => $descripcion) {
    echo "\n📋 $descripcion...\n";
    echo "💻 Ejecutando: $comando\n";
    
    $output = [];
    $returnCode = 0;
    exec($comando . ' 2>&1', $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ Comando ejecutado exitosamente\n";
        if (!empty($output)) {
            echo "📄 Salida: " . implode("\n", $output) . "\n";
        }
    } else {
        echo "❌ ERROR al ejecutar comando\n";
        echo "📄 Salida: " . implode("\n", $output) . "\n";
    }
}

echo "\n🎉 ¡Configuración completada!\n";
echo "🌐 Accede a: http://localhost:8000\n";
echo "📊 Base de datos: csdt_veeduria\n";
echo "👤 Usuario por defecto: admin@csdt.com\n";
echo "🔑 Contraseña: password\n\n";
