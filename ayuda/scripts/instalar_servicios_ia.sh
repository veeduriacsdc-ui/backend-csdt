#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE SERVICIOS DE IA
# CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL
# ===========================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
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

print_header "INSTALACIÓN DE SERVICIOS DE IA"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# VERIFICAR SERVICIOS DE IA EXISTENTES
# ===========================================
print_message "Verificando servicios de IA existentes..."

cd /var/www/frontend-csdt/src/services

# Lista de servicios de IA que deben estar presentes
SERVICIOS_IA=(
    "IAMejoradaService.js"
    "IAsProfesionalesService.js"
    "SistemaIAProfesionalService.js"
    "ChatGPTMejoradoService.js"
    "IAsTecnicasService.js"
    "ConsejoIAService.js"
    "AnalisisJuridicoService.js"
    "AnalisisNarrativoProfesionalService.js"
    "ConsejoVeeduriaTerritorialService.js"
)

print_message "Servicios de IA encontrados:"
for servicio in "${SERVICIOS_IA[@]}"; do
    if [ -f "$servicio" ]; then
        print_success "✅ $servicio"
    else
        print_warning "⚠️ $servicio no encontrado"
    fi
done

# ===========================================
# CREAR SERVICIOS DE IA FALTANTES
# ===========================================
print_message "Creando servicios de IA faltantes..."

# Servicio de IA Mejorada
if [ ! -f "IAMejoradaService.js" ]; then
    print_message "Creando IAMejoradaService.js..."
    cat > IAMejoradaService.js << 'EOF'
// Servicio de IA Mejorada para CSDT
class IAMejoradaService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarTexto(texto) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ texto })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAMejoradaService:', error);
            throw error;
        }
    }

    async generarRespuesta(pregunta) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/respuesta`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ pregunta })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAMejoradaService:', error);
            throw error;
        }
    }
}

export default new IAMejoradaService();
EOF
    print_success "✅ IAMejoradaService.js creado"
fi

# Servicio de IA Profesionales
if [ ! -f "IAsProfesionalesService.js" ]; then
    print_message "Creando IAsProfesionalesService.js..."
    cat > IAsProfesionalesService.js << 'EOF'
// Servicio de IA Profesionales para CSDT
class IAsProfesionalesService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarDocumento(documento) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/profesionales/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ documento })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAsProfesionalesService:', error);
            throw error;
        }
    }

    async generarInforme(datos) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/profesionales/informe`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ datos })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAsProfesionalesService:', error);
            throw error;
        }
    }
}

export default new IAsProfesionalesService();
EOF
    print_success "✅ IAsProfesionalesService.js creado"
fi

# Servicio de Sistema IA Profesional
if [ ! -f "SistemaIAProfesionalService.js" ]; then
    print_message "Creando SistemaIAProfesionalService.js..."
    cat > SistemaIAProfesionalService.js << 'EOF'
// Sistema IA Profesional para CSDT
class SistemaIAProfesionalService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async procesarCaso(caso) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/sistema/procesar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ caso })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en SistemaIAProfesionalService:', error);
            throw error;
        }
    }

    async generarRecomendacion(datos) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/sistema/recomendacion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ datos })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en SistemaIAProfesionalService:', error);
            throw error;
        }
    }
}

export default new SistemaIAProfesionalService();
EOF
    print_success "✅ SistemaIAProfesionalService.js creado"
fi

# Servicio de ChatGPT Mejorado
if [ ! -f "ChatGPTMejoradoService.js" ]; then
    print_message "Creando ChatGPTMejoradoService.js..."
    cat > ChatGPTMejoradoService.js << 'EOF'
// ChatGPT Mejorado para CSDT
class ChatGPTMejoradoService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async enviarMensaje(mensaje) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/chat/mensaje`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ mensaje })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ChatGPTMejoradoService:', error);
            throw error;
        }
    }

    async obtenerHistorial() {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/chat/historial`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ChatGPTMejoradoService:', error);
            throw error;
        }
    }
}

export default new ChatGPTMejoradoService();
EOF
    print_success "✅ ChatGPTMejoradoService.js creado"
fi

# Servicio de IA Técnicas
if [ ! -f "IAsTecnicasService.js" ]; then
    print_message "Creando IAsTecnicasService.js..."
    cat > IAsTecnicasService.js << 'EOF'
// Servicio de IA Técnicas para CSDT
class IAsTecnicasService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarTecnico(documento) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/tecnicas/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ documento })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAsTecnicasService:', error);
            throw error;
        }
    }

    async generarReporteTecnico(datos) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/tecnicas/reporte`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ datos })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en IAsTecnicasService:', error);
            throw error;
        }
    }
}

export default new IAsTecnicasService();
EOF
    print_success "✅ IAsTecnicasService.js creado"
fi

# Servicio de Consejo IA
if [ ! -f "ConsejoIAService.js" ]; then
    print_message "Creando ConsejoIAService.js..."
    cat > ConsejoIAService.js << 'EOF'
// Servicio de Consejo IA para CSDT
class ConsejoIAService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async obtenerConsejo(consulta) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/consejo`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ consulta })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ConsejoIAService:', error);
            throw error;
        }
    }

    async generarRecomendacion(problema) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/consejo/recomendacion`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ problema })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ConsejoIAService:', error);
            throw error;
        }
    }
}

export default new ConsejoIAService();
EOF
    print_success "✅ ConsejoIAService.js creado"
fi

# Servicio de Análisis Jurídico
if [ ! -f "AnalisisJuridicoService.js" ]; then
    print_message "Creando AnalisisJuridicoService.js..."
    cat > AnalisisJuridicoService.js << 'EOF'
// Servicio de Análisis Jurídico para CSDT
class AnalisisJuridicoService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarDocumentoJuridico(documento) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/juridico/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ documento })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en AnalisisJuridicoService:', error);
            throw error;
        }
    }

    async generarDictamen(datos) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/juridico/dictamen`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ datos })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en AnalisisJuridicoService:', error);
            throw error;
        }
    }
}

export default new AnalisisJuridicoService();
EOF
    print_success "✅ AnalisisJuridicoService.js creado"
fi

# Servicio de Análisis Narrativo Profesional
if [ ! -f "AnalisisNarrativoProfesionalService.js" ]; then
    print_message "Creando AnalisisNarrativoProfesionalService.js..."
    cat > AnalisisNarrativoProfesionalService.js << 'EOF'
// Servicio de Análisis Narrativo Profesional para CSDT
class AnalisisNarrativoProfesionalService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarNarrativa(texto) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/narrativo/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ texto })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en AnalisisNarrativoProfesionalService:', error);
            throw error;
        }
    }

    async generarResumen(documento) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/narrativo/resumen`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ documento })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en AnalisisNarrativoProfesionalService:', error);
            throw error;
        }
    }
}

export default new AnalisisNarrativoProfesionalService();
EOF
    print_success "✅ AnalisisNarrativoProfesionalService.js creado"
fi

# Servicio de Consejo Veeduría Territorial
if [ ! -f "ConsejoVeeduriaTerritorialService.js" ]; then
    print_message "Creando ConsejoVeeduriaTerritorialService.js..."
    cat > ConsejoVeeduriaTerritorialService.js << 'EOF'
// Servicio de Consejo Veeduría Territorial para CSDT
class ConsejoVeeduriaTerritorialService {
    constructor() {
        this.apiUrl = import.meta.env.VITE_API_URL || 'http://64.225.113.49:8000';
        this.timeout = import.meta.env.VITE_IA_TIMEOUT || 30000;
    }

    async analizarTerritorio(datos) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/territorial/analizar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ datos })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ConsejoVeeduriaTerritorialService:', error);
            throw error;
        }
    }

    async generarPlanTerritorial(territorio) {
        try {
            const response = await fetch(`${this.apiUrl}/api/ia/territorial/plan`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ territorio })
            });

            if (!response.ok) {
                throw new Error(`Error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('Error en ConsejoVeeduriaTerritorialService:', error);
            throw error;
        }
    }
}

export default new ConsejoVeeduriaTerritorialService();
EOF
    print_success "✅ ConsejoVeeduriaTerritorialService.js creado"
fi

# ===========================================
# CONFIGURAR PERMISOS
# ===========================================
print_message "Configurando permisos..."

chown -R www-data:www-data /var/www/frontend-csdt/src/services
chmod -R 644 /var/www/frontend-csdt/src/services/*.js

print_success "✅ Permisos configurados"

# ===========================================
# VERIFICAR INSTALACIÓN
# ===========================================
print_message "Verificando instalación de servicios de IA..."

cd /var/www/frontend-csdt/src/services

print_message "Servicios de IA instalados:"
for servicio in "${SERVICIOS_IA[@]}"; do
    if [ -f "$servicio" ]; then
        print_success "✅ $servicio"
    else
        print_error "❌ $servicio no encontrado"
    fi
done

# ===========================================
# COMPILAR FRONTEND
# ===========================================
print_message "Compilando frontend con servicios de IA..."

cd /var/www/frontend-csdt
npm run build

print_success "✅ Frontend compilado con servicios de IA"

# ===========================================
# REINICIAR SERVICIOS
# ===========================================
print_message "Reiniciando servicios..."

pm2 restart frontend-csdt

print_success "✅ Servicios reiniciados"

print_header "INSTALACIÓN DE SERVICIOS DE IA COMPLETADA"

print_success "✅ Todos los servicios de IA instalados correctamente"
print_message "Los servicios están disponibles en el frontend"
print_message "Para verificar, revisa la consola del navegador"
