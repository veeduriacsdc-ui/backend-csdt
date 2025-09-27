#!/bin/bash

# Script de verificación de salud del sistema CSDT
echo "🔍 Verificando salud del sistema CSDT..."

# Configuración
API_URL="http://134.209.221.193/api"
FRONTEND_URL="http://134.209.221.193"

# Función para verificar endpoint
check_endpoint() {
    local url=$1
    local name=$2
    local expected_status=${3:-200}
    
    echo "🔍 Verificando $name..."
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "$url" --connect-timeout 10)
    
    if [ "$response" -eq "$expected_status" ]; then
        echo "✅ $name: OK (HTTP $response)"
        return 0
    else
        echo "❌ $name: ERROR (HTTP $response)"
        return 1
    fi
}

# Verificar endpoints principales
echo "📊 Verificando endpoints del sistema..."

# Frontend
check_endpoint "$FRONTEND_URL" "Frontend" 200

# API Health
check_endpoint "$API_URL/health" "API Health" 200

# API Pública
check_endpoint "$API_URL/publico/tipos-veeduria" "API Pública" 200

# API de Autenticación (debe devolver 422 sin datos)
check_endpoint "$API_URL/auth/login" "API Auth" 422

echo ""
echo "📋 Resumen de verificación:"
echo "   - Frontend: $FRONTEND_URL"
echo "   - API: $API_URL"
echo "   - Health Check: $API_URL/health"
echo "   - Documentación API: $API_URL/publico/*"

echo ""
echo "🔧 Para verificar manualmente:"
echo "   curl $API_URL/health"
echo "   curl $FRONTEND_URL"
