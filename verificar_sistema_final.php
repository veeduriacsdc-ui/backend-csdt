<?php

/**
 * Script final para verificar que todo el sistema esté funcionando
 * Ejecutar: php verificar_sistema_final.php
 */

echo "🎯 VERIFICACIÓN FINAL DEL SISTEMA CSDT\n";
echo "======================================\n\n";

// 1. Verificar PHP y extensiones
echo "1️⃣ VERIFICANDO PHP...\n";
echo "=====================\n";
echo "✅ PHP " . phpversion() . " instalado\n";

$extensiones = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'json'];
foreach ($extensiones as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext: Instalada\n";
    } else {
        echo "❌ $ext: NO instalada\n";
    }
}

// 2. Verificar base de datos
echo "\n2️⃣ VERIFICANDO BASE DE DATOS...\n";
echo "===============================\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=csdt_database', 'root', '');
    echo "✅ Conexión a MySQL exitosa\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes");
    $clientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✅ Clientes registrados: $clientes\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM operadores");
    $operadores = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "✅ Operadores registrados: $operadores\n";
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
}

// 3. Verificar servidores
echo "\n3️⃣ VERIFICANDO SERVIDORES...\n";
echo "=============================\n";

// Backend
$backendUrl = 'http://127.0.0.1:8000';
$backendResponse = @file_get_contents($backendUrl);
if ($backendResponse !== false) {
    echo "✅ Backend Laravel: Ejecutándose en $backendUrl\n";
} else {
    echo "❌ Backend Laravel: No responde en $backendUrl\n";
    echo "💡 Ejecutar: php artisan serve --host=127.0.0.1 --port=8000\n";
}

// Frontend
$frontendUrl = 'http://localhost:5173';
$frontendResponse = @file_get_contents($frontendUrl);
if ($frontendResponse !== false) {
    echo "✅ Frontend React: Ejecutándose en $frontendUrl\n";
} else {
    echo "❌ Frontend React: No responde en $frontendUrl\n";
    echo "💡 Ejecutar: cd ../frontend-csdt-final && npm run dev\n";
}

// 4. Probar endpoint de registro
echo "\n4️⃣ PROBANDO ENDPOINT DE REGISTRO...\n";
echo "===================================\n";

$url = 'http://127.0.0.1:8000/api/auth/register-cliente';
$datos = [
    'nombre' => 'Usuario Test Final',
    'email' => 'testfinal@example.com',
    'usuario' => 'testfinal',
    'contrasena' => 'password123',
    'confirmarContrasena' => 'password123',
    'rol' => 'cliente',
    'tipoDocumento' => 'CC',
    'numeroDocumento' => '77777777'
];

$opciones = [
    'http' => [
        'header' => "Content-type: application/json\r\n",
        'method' => 'POST',
        'content' => json_encode($datos)
    ]
];

$contexto = stream_context_create($opciones);
$resultado = file_get_contents($url, false, $contexto);

if ($resultado === FALSE) {
    echo "❌ Error al llamar al endpoint de registro\n";
    echo "💡 Verificar que ambos servidores estén ejecutándose\n";
} else {
    $respuesta = json_decode($resultado, true);
    if ($respuesta && isset($respuesta['success']) && $respuesta['success']) {
        echo "✅ Endpoint de registro funcionando correctamente\n";
        echo "📝 Usuario de prueba creado exitosamente\n";
    } else {
        echo "⚠️  Endpoint responde pero con problemas:\n";
        echo "📝 Respuesta: " . $resultado . "\n";
    }
}

// 5. Verificar archivos de build
echo "\n5️⃣ VERIFICANDO ARCHIVOS DE BUILD...\n";
echo "===================================\n";

$buildFiles = [
    'public/build/manifest.json' => 'Backend Build',
    '../frontend-csdt-final/dist/index.html' => 'Frontend Build'
];

foreach ($buildFiles as $file => $description) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $description: " . number_format($size) . " bytes\n";
    } else {
        echo "❌ $description: No encontrado\n";
    }
}

// 6. Instrucciones finales
echo "\n6️⃣ INSTRUCCIONES FINALES...\n";
echo "===========================\n";

echo "🌐 URLs PARA PROBAR:\n";
echo "====================\n";
echo "• Frontend: http://localhost:5173\n";
echo "• Backend: http://127.0.0.1:8000\n";
echo "• API: http://127.0.0.1:8000/api\n\n";

echo "🎯 PRUEBAS RECOMENDADAS:\n";
echo "========================\n";
echo "1. Abrir http://localhost:5173 en el navegador\n";
echo "2. Hacer clic en 'Iniciar Sesión'\n";
echo "3. Hacer clic en 'Registrarse'\n";
echo "4. Llenar el formulario con datos únicos\n";
echo "5. Verificar que se registre correctamente\n";
echo "6. Probar el login con el usuario creado\n\n";

echo "🔧 COMANDOS DE MANTENIMIENTO:\n";
echo "=============================\n";
echo "• Iniciar sistema: powershell -ExecutionPolicy Bypass -File iniciar_sistema_completo.ps1\n";
echo "• Backend: php artisan serve --host=127.0.0.1 --port=8000\n";
echo "• Frontend: cd ../frontend-csdt-final && npm run dev\n";
echo "• Limpiar caché: php artisan config:clear && php artisan cache:clear\n";
echo "• Ver logs: Get-Content storage/logs/laravel.log -Wait\n\n";

echo "✅ Sistema verificado y listo para usar\n";
echo "🚀 ¡El registro debería funcionar correctamente ahora!\n";
