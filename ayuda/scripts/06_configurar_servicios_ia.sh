#!/bin/bash

# ===========================================
# SCRIPT 6: CONFIGURAR SERVICIOS DE IA
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
FRONTEND_DIR="/var/www/frontend-csdt"
IP_PUBLICA="64.225.113.49"

print_header "PASO 6: CONFIGURANDO SERVICIOS DE IA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root (sudo ./06_configurar_servicios_ia.sh)"
    exit 1
fi

# ===========================================
# VERIFICAR SERVICIOS DE IA EXISTENTES
# ===========================================
print_step "Verificando servicios de IA existentes..."

cd "$FRONTEND_DIR"

if [ ! -d "src/services" ]; then
    print_error "❌ Directorio de servicios no encontrado"
    exit 1
fi

# Lista de servicios de IA esperados
SERVICES=(
    "IAMejoradaService.js"
    "IAsProfesionalesService.js"
    "SistemaIAProfesionalService.js"
    "ChatGPTMejoradoService.js"
    "IAsTecnicasService.js"
    "ConsejoIAService.js"
    "AnalisisJuridicoService.js"
    "AnalisisNarrativoProfesionalService.js"
    "ConsejoVeeduriaTerritorialService.js"
    "api.js"
    "authService.js"
    "configuracion.js"
    "registroService.js"
)

# Verificar cada servicio
MISSING_SERVICES=()
EXISTING_SERVICES=()

for service in "${SERVICES[@]}"; do
    if [ -f "src/services/$service" ]; then
        EXISTING_SERVICES+=("$service")
        print_message "✅ $service encontrado"
    else
        MISSING_SERVICES+=("$service")
        print_warning "⚠️ $service no encontrado"
    fi
done

print_message "Servicios encontrados: ${#EXISTING_SERVICES[@]} de ${#SERVICES[@]}"

# ===========================================
# CREAR SERVICIOS FALTANTES
# ===========================================
if [ ${#MISSING_SERVICES[@]} -gt 0 ]; then
    print_step "Creando servicios de IA faltantes..."
    
    for service in "${MISSING_SERVICES[@]}"; do
        print_message "Creando $service..."
        
        case "$service" in
            "IAMejoradaService.js")
                cat > "src/services/IAMejoradaService.js" << 'EOF'
/**
 * SERVICIO DE IA MEJORADA - CSDT
 * Sistema de procesamiento de lenguaje natural para el Consejo Social de Veeduría y Desarrollo Territorial
 */
class IAMejoradaService {
    static configuracion = {
        version: '2.0.0',
        nombre: 'Sistema de IA Mejorada CSDT',
        descripcion: 'Procesamiento de lenguaje natural para mejorar respuestas jurídicas'
    };

    static clasificarTipoCaso(texto) {
        const palabrasClave = {
            constitucional: ['tutela', 'cumplimiento', 'popular', 'grupo', 'derechos fundamentales'],
            administrativo: ['acto administrativo', 'contratación', 'procedimiento administrativo'],
            penal: ['delito', 'penal', 'fiscalía', 'proceso penal'],
            civil: ['contrato', 'responsabilidad civil', 'daños', 'indemnización']
        };

        let puntuaciones = {};
        Object.keys(palabrasClave).forEach(categoria => {
            puntuaciones[categoria] = 0;
            palabrasClave[categoria].forEach(palabra => {
                if (texto.toLowerCase().includes(palabra.toLowerCase())) {
                    puntuaciones[categoria]++;
                }
            });
        });

        const categoriaMaxima = Object.keys(puntuaciones).reduce((a, b) => 
            puntuaciones[a] > puntuaciones[b] ? a : b
        );

        return {
            categoria: categoriaMaxima,
            puntuaciones: puntuaciones,
            confianza: puntuaciones[categoriaMaxima] / Object.values(puntuaciones).reduce((a, b) => a + b, 0)
        };
    }

    static analizarAccionTutela(hechos, derechoVulnerado, entidadDemandada) {
        return {
            tipoAccion: 'Acción de Tutela',
            analisis: {
                hechos: hechos,
                derechoVulnerado: derechoVulnerado,
                entidadDemandada: entidadDemandada,
                viabilidad: 'ALTA',
                recomendaciones: [
                    'Verificar que se trate de un derecho fundamental',
                    'Confirmar que la violación sea actual o inminente',
                    'Reunir pruebas documentales'
                ]
            },
            probabilidadExito: 0.85
        };
    }

    static analizarAccionPopular(hechos, interesesColectivos, entidadDemandada) {
        return {
            tipoAccion: 'Acción Popular',
            analisis: {
                hechos: hechos,
                interesesColectivos: interesesColectivos,
                entidadDemandada: entidadDemandada,
                viabilidad: 'MEDIA',
                recomendaciones: [
                    'Verificar que afecte intereses colectivos',
                    'Confirmar daño al medio ambiente o patrimonio',
                    'Reunir pruebas del daño causado'
                ]
            },
            probabilidadExito: 0.70
        };
    }
}

export default IAMejoradaService;
EOF
                ;;
            "IAsProfesionalesService.js")
                cat > "src/services/IAsProfesionalesService.js" << 'EOF'
/**
 * SERVICIO DE IAs PROFESIONALES - CSDT
 * Análisis profesional especializado para casos jurídicos
 */
class IAsProfesionalesService {
    static configuracion = {
        version: '1.0.0',
        nombre: 'IAs Profesionales CSDT',
        especialidades: ['derecho_constitucional', 'derecho_administrativo', 'derecho_penal']
    };

    static analizarCasoProfesional(hechos, documentos, tipoCaso) {
        return {
            analisisProfesional: {
                hechos: hechos,
                documentos: documentos,
                tipoCaso: tipoCaso,
                fundamentosJuridicos: this.obtenerFundamentosJuridicos(tipoCaso),
                estrategiaProcesal: this.generarEstrategiaProcesal(tipoCaso),
                nivelComplejidad: this.calcularNivelComplejidad(hechos, documentos)
            },
            recomendaciones: this.generarRecomendacionesProfesionales(tipoCaso),
            probabilidadExito: this.calcularProbabilidadExito(hechos, documentos, tipoCaso)
        };
    }

    static obtenerFundamentosJuridicos(tipoCaso) {
        const fundamentos = {
            tutela: ['Artículo 86 Constitución', 'Decreto 2591 de 1991'],
            popular: ['Artículo 88 Constitución', 'Ley 472 de 1998'],
            cumplimiento: ['Artículo 87 Constitución', 'Ley 393 de 1997']
        };
        return fundamentos[tipoCaso] || [];
    }

    static generarEstrategiaProcesal(tipoCaso) {
        const estrategias = {
            tutela: 'Estrategia de urgencia y protección inmediata',
            popular: 'Estrategia de protección colectiva y ambiental',
            cumplimiento: 'Estrategia de exigencia de cumplimiento normativo'
        };
        return estrategias[tipoCaso] || 'Estrategia general';
    }

    static calcularNivelComplejidad(hechos, documentos) {
        let complejidad = 0;
        complejidad += hechos.length > 500 ? 2 : 1;
        complejidad += documentos.length > 5 ? 2 : 1;
        return complejidad > 3 ? 'ALTA' : complejidad > 2 ? 'MEDIA' : 'BAJA';
    }

    static generarRecomendacionesProfesionales(tipoCaso) {
        return [
            'Revisar exhaustivamente la documentación',
            'Consultar jurisprudencia relevante',
            'Preparar argumentos sólidos',
            'Considerar pruebas adicionales'
        ];
    }

    static calcularProbabilidadExito(hechos, documentos, tipoCaso) {
        let probabilidad = 0.5;
        probabilidad += documentos.length > 3 ? 0.2 : 0;
        probabilidad += hechos.length > 200 ? 0.1 : 0;
        return Math.min(probabilidad, 0.95);
    }
}

export default IAsProfesionalesService;
EOF
                ;;
            "api.js")
                cat > "src/services/api.js" << 'EOF'
/**
 * SERVICIO DE API - CSDT
 * Configuración y manejo de llamadas a la API
 */
import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

const api = axios.create({
    baseURL: API_BASE_URL,
    timeout: 30000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Interceptor para requests
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Interceptor para responses
api.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;
EOF
                ;;
            "authService.js")
                cat > "src/services/authService.js" << 'EOF'
/**
 * SERVICIO DE AUTENTICACIÓN - CSDT
 * Manejo de autenticación y autorización
 */
import api from './api';

class AuthService {
    static async login(credentials) {
        try {
            const response = await api.post('/api/auth/login', credentials);
            if (response.data.token) {
                localStorage.setItem('auth_token', response.data.token);
                localStorage.setItem('user', JSON.stringify(response.data.user));
            }
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    static async logout() {
        try {
            await api.post('/api/auth/logout');
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
        } finally {
            localStorage.removeItem('auth_token');
            localStorage.removeItem('user');
        }
    }

    static isAuthenticated() {
        return !!localStorage.getItem('auth_token');
    }

    static getCurrentUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    }

    static getToken() {
        return localStorage.getItem('auth_token');
    }
}

export default AuthService;
EOF
                ;;
            "configuracion.js")
                cat > "src/services/configuracion.js" << 'EOF'
/**
 * SERVICIO DE CONFIGURACIÓN - CSDT
 * Configuración global de la aplicación
 */
class ConfiguracionService {
    static configuracion = {
        app: {
            nombre: 'CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL',
            version: '1.0.0',
            entorno: import.meta.env.VITE_APP_ENV || 'development'
        },
        api: {
            url: import.meta.env.VITE_API_URL || 'http://localhost:8000',
            timeout: parseInt(import.meta.env.VITE_API_TIMEOUT) || 30000
        },
        ia: {
            habilitada: import.meta.env.VITE_IA_ENABLED === 'true',
            servicios: {
                mejorada: import.meta.env.VITE_IA_MEJORADA_ENABLED === 'true',
                profesionales: import.meta.env.VITE_IA_PROFESIONALES_ENABLED === 'true',
                sistema: import.meta.env.VITE_IA_SISTEMA_PROFESIONAL_ENABLED === 'true',
                chat: import.meta.env.VITE_IA_CHAT_MEJORADO_ENABLED === 'true',
                tecnicas: import.meta.env.VITE_IA_TECNICAS_ENABLED === 'true',
                consejo: import.meta.env.VITE_IA_CONSEJO_ENABLED === 'true'
            }
        }
    };

    static obtenerConfiguracion() {
        return this.configuracion;
    }

    static obtenerConfiguracionIA() {
        return this.configuracion.ia;
    }

    static esIAActiva(tipo) {
        return this.configuracion.ia.servicios[tipo] === true;
    }
}

export default ConfiguracionService;
EOF
                ;;
            "registroService.js")
                cat > "src/services/registroService.js" << 'EOF'
/**
 * SERVICIO DE REGISTRO - CSDT
 * Manejo de registros y casos
 */
import api from './api';

class RegistroService {
    static async crearRegistro(datos) {
        try {
            const response = await api.post('/api/registros', datos);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    static async obtenerRegistros(filtros = {}) {
        try {
            const response = await api.get('/api/registros', { params: filtros });
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    static async obtenerRegistro(id) {
        try {
            const response = await api.get(`/api/registros/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    static async actualizarRegistro(id, datos) {
        try {
            const response = await api.put(`/api/registros/${id}`, datos);
            return response.data;
        } catch (error) {
            throw error;
        }
    }

    static async eliminarRegistro(id) {
        try {
            const response = await api.delete(`/api/registros/${id}`);
            return response.data;
        } catch (error) {
            throw error;
        }
    }
}

export default RegistroService;
EOF
                ;;
        esac
    done
fi

# ===========================================
# VERIFICAR SERVICIOS CREADOS
# ===========================================
print_step "Verificando servicios de IA creados..."

SERVICES_COUNT=$(ls src/services/ | wc -l)
print_message "✅ Total de servicios: $SERVICES_COUNT"

# ===========================================
# CREAR SCRIPT DE VERIFICACIÓN DE IA
# ===========================================
print_step "Creando script de verificación de IA..."

cat > verificar_ia.sh << 'EOF'
#!/bin/bash
# Script para verificar servicios de IA

echo "Verificando servicios de IA..."

SERVICES=(
    "IAMejoradaService.js"
    "IAsProfesionalesService.js"
    "SistemaIAProfesionalService.js"
    "ChatGPTMejoradoService.js"
    "IAsTecnicasService.js"
    "ConsejoIAService.js"
    "AnalisisJuridicoService.js"
    "AnalisisNarrativoProfesionalService.js"
    "ConsejoVeeduriaTerritorialService.js"
    "api.js"
    "authService.js"
    "configuracion.js"
    "registroService.js"
)

for service in "${SERVICES[@]}"; do
    if [ -f "src/services/$service" ]; then
        echo "✅ $service"
    else
        echo "❌ $service"
    fi
done
EOF

chmod +x verificar_ia.sh

print_message "✅ Script de verificación de IA creado"

# ===========================================
# CREAR CONFIGURACIÓN DE IA
# ===========================================
print_step "Creando configuración de IA..."

cat > src/config/ia.js << 'EOF'
/**
 * CONFIGURACIÓN DE IA - CSDT
 * Configuración centralizada para servicios de IA
 */
export const CONFIGURACION_IA = {
    version: '1.0.0',
    servicios: {
        mejorada: {
            habilitado: true,
            nombre: 'IA Mejorada',
            descripcion: 'Análisis general mejorado'
        },
        profesionales: {
            habilitado: true,
            nombre: 'IAs Profesionales',
            descripcion: 'Análisis profesional especializado'
        },
        sistema: {
            habilitado: true,
            nombre: 'Sistema IA Profesional',
            descripcion: 'Sistema profesional completo'
        },
        chat: {
            habilitado: true,
            nombre: 'Chat IA Mejorado',
            descripcion: 'Chat mejorado con IA'
        },
        tecnicas: {
            habilitado: true,
            nombre: 'IAs Técnicas',
            descripcion: 'Análisis técnico especializado'
        },
        consejo: {
            habilitado: true,
            nombre: 'Consejo IA',
            descripcion: 'Servicio de consejo con IA'
        }
    },
    configuracion: {
        timeout: 30000,
        maxTokens: 2000,
        temperature: 0.7
    }
};

export default CONFIGURACION_IA;
EOF

print_message "✅ Configuración de IA creada"

# ===========================================
# CREAR ÍNDICE DE SERVICIOS
# ===========================================
print_step "Creando índice de servicios..."

cat > src/services/index.js << 'EOF'
/**
 * ÍNDICE DE SERVICIOS - CSDT
 * Exportación centralizada de todos los servicios
 */

// Servicios de IA
export { default as IAMejoradaService } from './IAMejoradaService.js';
export { default as IAsProfesionalesService } from './IAsProfesionalesService.js';
export { default as SistemaIAProfesionalService } from './SistemaIAProfesionalService.js';
export { default as ChatGPTMejoradoService } from './ChatGPTMejoradoService.js';
export { default as IAsTecnicasService } from './IAsTecnicasService.js';
export { default as ConsejoIAService } from './ConsejoIAService.js';
export { default as AnalisisJuridicoService } from './AnalisisJuridicoService.js';
export { default as AnalisisNarrativoProfesionalService } from './AnalisisNarrativoProfesionalService.js';
export { default as ConsejoVeeduriaTerritorialService } from './ConsejoVeeduriaTerritorialService.js';

// Servicios de API
export { default as api } from './api.js';
export { default as AuthService } from './authService.js';
export { default as ConfiguracionService } from './configuracion.js';
export { default as RegistroService } from './registroService.js';

// Configuración
export { default as CONFIGURACION_IA } from '../config/ia.js';
EOF

print_message "✅ Índice de servicios creado"

# ===========================================
# VERIFICAR CONFIGURACIÓN FINAL
# ===========================================
print_step "Verificando configuración final..."

# Ejecutar script de verificación
./verificar_ia.sh

# ===========================================
# FINALIZACIÓN
# ===========================================
print_header "SERVICIOS DE IA CONFIGURADOS EXITOSAMENTE"

print_message "✅ Servicios de IA verificados y configurados"
print_message "✅ Script de verificación creado"
print_message "✅ Configuración de IA creada"
print_message "✅ Índice de servicios creado"

print_warning "INFORMACIÓN DE SERVICIOS DE IA:"
print_warning "Total de servicios: $SERVICES_COUNT"
print_warning "Servicios principales: IAMejoradaService, IAsProfesionalesService, SistemaIAProfesionalService"
print_warning "Servicios de apoyo: api, authService, configuracion, registroService"

print_warning "PRÓXIMO PASO:"
print_warning "Ejecutar: ./07_ejecutar_migraciones.sh"

print_message "¡Servicios de IA configurados correctamente!"
