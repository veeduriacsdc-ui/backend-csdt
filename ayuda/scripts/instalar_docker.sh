#!/bin/bash

# ===========================================
# SCRIPT DE INSTALACIÓN DE DOCKER
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

print_header "INSTALACIÓN DE DOCKER"

# Verificar que estamos como root
if [ "$EUID" -ne 0 ]; then
    print_error "Por favor ejecuta este script como root"
    exit 1
fi

# ===========================================
# INSTALAR DOCKER
# ===========================================
print_message "Instalando Docker..."

# Actualizar paquetes
apt update

# Instalar dependencias
apt install -y apt-transport-https ca-certificates curl gnupg lsb-release

# Agregar clave GPG de Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# Agregar repositorio de Docker
echo "deb [arch=amd64 signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

# Actualizar paquetes
apt update

# Instalar Docker
apt install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

print_success "✅ Docker instalado"

# ===========================================
# CONFIGURAR DOCKER
# ===========================================
print_message "Configurando Docker..."

# Agregar usuario www-data al grupo docker
usermod -aG docker www-data

# Configurar Docker para inicio automático
systemctl enable docker
systemctl start docker

print_success "✅ Docker configurado"

# ===========================================
# CREAR DOCKERFILE PARA CSDT
# ===========================================
print_message "Creando Dockerfile para CSDT..."

# Crear Dockerfile para backend
cat > /var/www/backend-csdt/Dockerfile << 'EOF'
# Dockerfile para Backend CSDT
FROM php:8.2-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libmysqlclient-dev

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Establecer directorio de trabajo
WORKDIR /var/www

# Copiar archivos de la aplicación
COPY . /var/www

# Instalar dependencias
RUN composer install --optimize-autoloader --no-dev

# Configurar permisos
RUN chown -R www-data:www-data /var/www
RUN chmod -R 755 /var/www
RUN chmod -R 775 /var/www/storage
RUN chmod -R 775 /var/www/bootstrap/cache

# Exponer puerto
EXPOSE 8000

# Comando por defecto
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
EOF

# Crear Dockerfile para frontend
cat > /var/www/frontend-csdt/Dockerfile << 'EOF'
# Dockerfile para Frontend CSDT
FROM node:18-alpine

# Establecer directorio de trabajo
WORKDIR /app

# Copiar archivos de la aplicación
COPY . /app

# Instalar dependencias
RUN npm install

# Compilar aplicación
RUN npm run build

# Instalar servidor estático
RUN npm install -g serve

# Exponer puerto
EXPOSE 3000

# Comando por defecto
CMD ["serve", "-s", "dist", "-l", "3000"]
EOF

print_success "✅ Dockerfiles creados"

# ===========================================
# CREAR DOCKER COMPOSE
# ===========================================
print_message "Creando Docker Compose..."

# Crear docker-compose.yml
cat > /var/www/docker-compose.yml << 'EOF'
version: '3.8'

services:
  # Base de datos MySQL
  mysql:
    image: mysql:8.0
    container_name: csdt_mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: csdt_final
      MYSQL_USER: csdt
      MYSQL_PASSWORD: 123
      MYSQL_ROOT_PASSWORD: 123
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - ./backend-csdt/database:/docker-entrypoint-initdb.d
    networks:
      - csdt_network

  # Backend Laravel
  backend:
    build: ./backend-csdt
    container_name: csdt_backend
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      - DB_CONNECTION=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=csdt_final
      - DB_USERNAME=csdt
      - DB_PASSWORD=123
    depends_on:
      - mysql
    volumes:
      - ./backend-csdt:/var/www
      - backend_storage:/var/www/storage
    networks:
      - csdt_network

  # Frontend React
  frontend:
    build: ./frontend-csdt
    container_name: csdt_frontend
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - VITE_API_URL=http://64.225.113.49:8000
    depends_on:
      - backend
    volumes:
      - ./frontend-csdt:/app
    networks:
      - csdt_network

  # Nginx (opcional)
  nginx:
    image: nginx:alpine
    container_name: csdt_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - frontend
      - backend
    networks:
      - csdt_network

volumes:
  mysql_data:
  backend_storage:

networks:
  csdt_network:
    driver: bridge
EOF

print_success "✅ Docker Compose creado"

# ===========================================
# CREAR CONFIGURACIÓN DE NGINX PARA DOCKER
# ===========================================
print_message "Creando configuración de Nginx para Docker..."

# Crear nginx.conf
cat > /var/www/nginx.conf << 'EOF'
events {
    worker_connections 1024;
}

http {
    upstream backend {
        server backend:8000;
    }

    upstream frontend {
        server frontend:3000;
    }

    server {
        listen 80;
        server_name 64.225.113.49;

        # Configuración de proxy para backend
        location /api/ {
            proxy_pass http://backend;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }

        # Configuración de proxy para frontend
        location / {
            proxy_pass http://frontend;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
}
EOF

print_success "✅ Configuración de Nginx creada"

# ===========================================
# CREAR SCRIPTS DE GESTIÓN DE DOCKER
# ===========================================
print_message "Creando scripts de gestión de Docker..."

# Script de inicio
cat > /usr/local/bin/iniciar_csdt_docker.sh << 'EOF'
#!/bin/bash
# Script de inicio de CSDT con Docker

echo "Iniciando CSDT con Docker..."
cd /var/www
docker-compose up -d

echo "Esperando a que los servicios estén listos..."
sleep 30

echo "Ejecutando migraciones..."
docker-compose exec backend php artisan migrate --force

echo "Ejecutando seeders..."
docker-compose exec backend php artisan db:seed --force

echo "CSDT iniciado con Docker"
EOF

chmod +x /usr/local/bin/iniciar_csdt_docker.sh

# Script de parada
cat > /usr/local/bin/detener_csdt_docker.sh << 'EOF'
#!/bin/bash
# Script de parada de CSDT con Docker

echo "Deteniendo CSDT con Docker..."
cd /var/www
docker-compose down

echo "CSDT detenido con Docker"
EOF

chmod +x /usr/local/bin/detener_csdt_docker.sh

# Script de reinicio
cat > /usr/local/bin/reiniciar_csdt_docker.sh << 'EOF'
#!/bin/bash
# Script de reinicio de CSDT con Docker

echo "Reiniciando CSDT con Docker..."
cd /var/www
docker-compose restart

echo "CSDT reiniciado con Docker"
EOF

chmod +x /usr/local/bin/reiniciar_csdt_docker.sh

# Script de logs
cat > /usr/local/bin/logs_csdt_docker.sh << 'EOF'
#!/bin/bash
# Script de logs de CSDT con Docker

echo "Mostrando logs de CSDT con Docker..."
cd /var/www
docker-compose logs -f
EOF

chmod +x /usr/local/bin/logs_csdt_docker.sh

# Script de estado
cat > /usr/local/bin/estado_csdt_docker.sh << 'EOF'
#!/bin/bash
# Script de estado de CSDT con Docker

echo "Estado de CSDT con Docker..."
cd /var/www
docker-compose ps

echo ""
echo "Uso de recursos:"
docker stats --no-stream
EOF

chmod +x /usr/local/bin/estado_csdt_docker.sh

print_success "✅ Scripts de gestión de Docker creados"

# ===========================================
# CONFIGURAR MONITOREO DE DOCKER
# ===========================================
print_message "Configurando monitoreo de Docker..."

# Crear script de monitoreo
cat > /usr/local/bin/monitor_docker.sh << 'EOF'
#!/bin/bash
# Script de monitoreo de Docker

echo "=== MONITOR DE DOCKER ==="
echo "Fecha: $(date)"
echo ""

# Estado de contenedores
echo "Estado de contenedores:"
docker ps -a

echo ""
echo "Uso de recursos:"
docker stats --no-stream

echo ""
echo "Espacio en disco:"
docker system df

echo ""
echo "Logs recientes:"
docker-compose logs --tail=10
EOF

chmod +x /usr/local/bin/monitor_docker.sh

print_success "✅ Monitoreo de Docker configurado"

# ===========================================
# CONFIGURAR BACKUP DE DOCKER
# ===========================================
print_message "Configurando backup de Docker..."

# Crear script de backup
cat > /usr/local/bin/backup_docker.sh << 'EOF'
#!/bin/bash
# Script de backup de Docker

BACKUP_DIR="/var/backups/docker_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Haciendo backup de Docker..."

# Backup de volúmenes
docker run --rm -v csdt_mysql_data:/data -v "$BACKUP_DIR":/backup alpine tar czf /backup/mysql_data.tar.gz -C /data .
docker run --rm -v csdt_backend_storage:/data -v "$BACKUP_DIR":/backup alpine tar czf /backup/backend_storage.tar.gz -C /data .

# Backup de configuración
cp /var/www/docker-compose.yml "$BACKUP_DIR/"
cp /var/www/nginx.conf "$BACKUP_DIR/"

# Comprimir backup
cd /var/backups
tar -czf "docker_$(date +%Y%m%d_%H%M%S).tar.gz" "docker_$(date +%Y%m%d_%H%M%S)"

# Eliminar directorio temporal
rm -rf "$BACKUP_DIR"

echo "Backup de Docker completado"
EOF

chmod +x /usr/local/bin/backup_docker.sh

print_success "✅ Backup de Docker configurado"

# ===========================================
# CONFIGURAR CRON JOBS DE DOCKER
# ===========================================
print_message "Configurando cron jobs de Docker..."

# Backup de Docker semanal
echo "0 1 * * 0 /usr/local/bin/backup_docker.sh >> /var/log/csdt_docker_backup.log 2>&1" | crontab -

print_success "✅ Cron jobs de Docker configurados"

# ===========================================
# VERIFICAR INSTALACIÓN DE DOCKER
# ===========================================
print_message "Verificando instalación de Docker..."

# Verificar Docker
docker --version
docker-compose --version

# Verificar estado
systemctl status docker --no-pager

print_success "✅ Instalación de Docker verificada"

print_header "INSTALACIÓN DE DOCKER COMPLETADA"

print_success "✅ Docker instalado correctamente"
print_message "Para iniciar CSDT con Docker: iniciar_csdt_docker.sh"
print_message "Para detener CSDT con Docker: detener_csdt_docker.sh"
print_message "Para reiniciar CSDT con Docker: reiniciar_csdt_docker.sh"
print_message "Para ver logs: logs_csdt_docker.sh"
print_message "Para ver estado: estado_csdt_docker.sh"
print_message "Para monitorear: monitor_docker.sh"
