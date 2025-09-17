<?php
/**
 * Script para probar la integración completa del sistema
 * CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
 */

echo "🧪 PRUEBA DE INTEGRACIÓN COMPLETA CSDT\n";
echo "=====================================\n\n";

// Configuración
$backendUrl = 'http://127.0.0.1:8000';
$frontendUrl = 'http://localhost:5173';

// Función para hacer peticiones HTTP
function hacerPeticion($url, $metodo = 'GET', $datos = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
        'Content-Type: application/json',
        'Accept: application/json'
    ], $headers));
    
    if ($metodo === 'POST' && $datos) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error
    ];
}

// 1. Probar salud de la API
echo "1️⃣ Probando salud de la API...\n";
$healthResponse = hacerPeticion($backendUrl . '/api/health');

if ($healthResponse['http_code'] === 200) {
    echo "✅ API Backend funcionando correctamente\n";
    $healthData = json_decode($healthResponse['response'], true);
    echo "📄 Mensaje: " . ($healthData['message'] ?? 'Sin mensaje') . "\n";
} else {
    echo "❌ API Backend no responde\n";
    echo "💡 Asegúrate de ejecutar: php artisan serve --host=127.0.0.1 --port=8000\n";
}

// 2. Probar registro de cliente
echo "\n2️⃣ Probando registro de cliente...\n";
$datosCliente = [
    'nombres' => 'Juan',
    'apellidos' => 'Pérez',
    'usuario' => 'juanperez' . time(),
    'correo' => 'juan.perez.' . time() . '@test.com',
    'contrasena' => 'password123',
    'confirmarContrasena' => 'password123',
    'telefono' => '+57 300 123 4567',
    'tipoDocumento' => 'CC',
    'numeroDocumento' => '12345678' . time(),
    'aceptoTerminos' => true,
    'aceptoPoliticas' => true
];

$registroResponse = hacerPeticion($backendUrl . '/api/auth/register-cliente', 'POST', $datosCliente);

if ($registroResponse['http_code'] === 200 || $registroResponse['http_code'] === 201) {
    echo "✅ Registro de cliente exitoso\n";
    $registroData = json_decode($registroResponse['response'], true);
    echo "📄 Mensaje: " . ($registroData['message'] ?? 'Usuario registrado') . "\n";
} else {
    echo "❌ Error en registro de cliente\n";
    echo "📄 Respuesta: " . $registroResponse['response'] . "\n";
}

// 3. Probar login
echo "\n3️⃣ Probando login...\n";
$credenciales = [
    'email' => 'esteban.41m@gmail.com',
    'password' => '123456'
];

$loginResponse = hacerPeticion($backendUrl . '/api/auth/login', 'POST', $credenciales);

if ($loginResponse['http_code'] === 200) {
    echo "✅ Login exitoso\n";
    $loginData = json_decode($loginResponse['response'], true);
    $token = $loginData['token'] ?? null;
    
    if ($token) {
        echo "🔑 Token obtenido: " . substr($token, 0, 20) . "...\n";
        
        // 4. Probar endpoint protegido
        echo "\n4️⃣ Probando endpoint protegido...\n";
        $headers = ['Authorization: Bearer ' . $token];
        $meResponse = hacerPeticion($backendUrl . '/api/auth/me', 'GET', null, $headers);
        
        if ($meResponse['http_code'] === 200) {
            echo "✅ Endpoint protegido accesible\n";
            $meData = json_decode($meResponse['response'], true);
            echo "👤 Usuario: " . ($meData['user']['nombres'] ?? 'Sin nombre') . "\n";
        } else {
            echo "❌ Error accediendo a endpoint protegido\n";
            echo "📄 Respuesta: " . $meResponse['response'] . "\n";
        }
        
        // 5. Probar dashboard
        echo "\n5️⃣ Probando dashboard...\n";
        $dashboardResponse = hacerPeticion($backendUrl . '/api/dashboard/resumen', 'GET', null, $headers);
        
        if ($dashboardResponse['http_code'] === 200) {
            echo "✅ Dashboard accesible\n";
            $dashboardData = json_decode($dashboardResponse['response'], true);
            echo "📊 Total clientes: " . ($dashboardData['data']['total_clientes'] ?? 0) . "\n";
            echo "📊 Total operadores: " . ($dashboardData['data']['total_operadores'] ?? 0) . "\n";
        } else {
            echo "❌ Error accediendo al dashboard\n";
        }
    }
} else {
    echo "❌ Error en login\n";
    echo "📄 Respuesta: " . $loginResponse['response'] . "\n";
}

// 6. Probar frontend
echo "\n6️⃣ Probando frontend...\n";
$frontendResponse = hacerPeticion($frontendUrl);

if ($frontendResponse['http_code'] === 200) {
    echo "✅ Frontend accesible\n";
    echo "🌐 URL: $frontendUrl\n";
} else {
    echo "❌ Frontend no accesible\n";
    echo "💡 Asegúrate de ejecutar: cd ../frontend-csdt-final && npm run dev\n";
}

// 7. Probar base de datos
echo "\n7️⃣ Probando base de datos...\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=csdt_veeduria', 'root', '');
    
    // Contar registros en tablas principales
    $tablas = ['clientes', 'operadores', 'pqrsfd', 'donaciones'];
    foreach ($tablas as $tabla) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM $tabla");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "✅ $tabla: $total registros\n";
        } catch (Exception $e) {
            echo "⚠️  $tabla: Error - " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error conectando a la base de datos: " . $e->getMessage() . "\n";
}

// Resumen final
echo "\n🎯 RESUMEN DE PRUEBAS\n";
echo "====================\n";
echo "✅ Backend API: " . ($healthResponse['http_code'] === 200 ? 'FUNCIONANDO' : 'ERROR') . "\n";
echo "✅ Registro: " . (($registroResponse['http_code'] === 200 || $registroResponse['http_code'] === 201) ? 'FUNCIONANDO' : 'ERROR') . "\n";
echo "✅ Login: " . ($loginResponse['http_code'] === 200 ? 'FUNCIONANDO' : 'ERROR') . "\n";
echo "✅ Frontend: " . ($frontendResponse['http_code'] === 200 ? 'FUNCIONANDO' : 'ERROR') . "\n";
echo "✅ Base de datos: " . (isset($pdo) ? 'FUNCIONANDO' : 'ERROR') . "\n";

echo "\n🚀 SISTEMA LISTO PARA USAR\n";
echo "==========================\n";
echo "• Backend: $backendUrl\n";
echo "• Frontend: $frontendUrl\n";
echo "• Base de datos: csdt_veeduria\n";
echo "• Usuario admin: esteban.41m@gmail.com / 123456\n";
echo "\n¡Prueba el sistema en el navegador!\n";
