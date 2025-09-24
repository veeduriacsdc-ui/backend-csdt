# Backend CSDT - API Laravel

## 📋 Descripción

API REST para el Sistema CSDT desarrollada con Laravel 12. Proporciona endpoints para la gestión de veedurías ciudadanas, donaciones, tareas y usuarios.

## 🌐 Información del Servidor

- **Servidor:** DigitalOcean Droplet
- **IP Pública:** 104.248.212.204
- **IP Privada:** 10.120.0.2
- **Región:** SFO2 (San Francisco)
- **Email:** veeduriacsdc@gmail.com
- **Repositorio Backend:** https://github.com/veeduriacsdc-ui/backend-csdt.git
- **Repositorio Frontend:** https://github.com/veeduriacsdc-ui/frontend-csdt.git

## 🚀 Características

- **API REST** completa con Laravel
- **Autenticación** con Laravel Sanctum
- **Base de datos** MySQL optimizada
- **Validaciones** robustas
- **Middleware** de autorización
- **Logs** del sistema
- **Documentación** de endpoints

## 🛠️ Tecnologías

- **Laravel 12** - Framework PHP
- **MySQL 8.0** - Base de datos
- **Laravel Sanctum** - Autenticación API
- **Eloquent ORM** - ORM de Laravel
- **Composer** - Gestor de dependencias

## 📁 Estructura del Proyecto

```
backend-csdt/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── Api/           # Controladores de API
│   │           ├── AuthController.php
│   │           ├── UsuarioController.php
│   │           ├── VeeduriaController.php
│   │           ├── DonacionController.php
│   │           ├── TareaController.php
│   │           ├── ArchivoController.php
│   │           ├── RolController.php
│   │           ├── ConfiguracionController.php
│   │           └── LogController.php
│   ├── Models/                # Modelos Eloquent
│   │   ├── Usuario.php
│   │   ├── Veeduria.php
│   │   ├── Donacion.php
│   │   ├── Tarea.php
│   │   ├── Archivo.php
│   │   ├── Rol.php
│   │   ├── Configuracion.php
│   │   └── Log.php
│   └── ...
├── database/
│   ├── migrations/            # Migraciones de BD
│   │   ├── 2025_01_01_000000_crear_tabla_usuarios.php
│   │   ├── 2025_01_01_000001_crear_tabla_veedurias.php
│   │   ├── 2025_01_01_000002_crear_tabla_donaciones.php
│   │   ├── 2025_01_01_000003_crear_tabla_tareas.php
│   │   ├── 2025_01_01_000004_crear_tabla_archivos.php
│   │   ├── 2025_01_01_000005_crear_tabla_roles.php
│   │   ├── 2025_01_01_000006_crear_tabla_configuraciones.php
│   │   └── 2025_01_01_000007_crear_tabla_logs.php
│   └── seeders/               # Seeders de datos
├── routes/
│   └── api.php               # Rutas de API
├── config/                   # Configuraciones
├── storage/                  # Archivos de almacenamiento
├── composer.json            # Dependencias
└── README.md               # Documentación
```

## 🚀 Instalación

### Prerrequisitos
- PHP 8.2+
- Composer
- MySQL 8.0+
- Laravel 12
- Git
- Acceso SSH al servidor DigitalOcean

### Instalación Local (Desarrollo)

1. **Clonar el repositorio**
```bash
git clone https://github.com/veeduriacsdc-ui/backend-csdt.git
cd backend-csdt
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
```

4. **Configurar base de datos en .env**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_database
DB_USERNAME=root
DB_PASSWORD=tu_password
```

5. **Generar clave de aplicación**
```bash
php artisan key:generate
```

6. **Ejecutar migraciones**
```bash
php artisan migrate
```

7. **Ejecutar seeders (opcional)**
```bash
php artisan db:seed
```

8. **Ejecutar servidor**
```bash
php artisan serve
```

### Instalación en Servidor DigitalOcean

#### Paso 1: Conectar al Servidor
```bash
# Conectar por SSH al servidor
ssh root@104.248.212.204
# Contraseña: Control-1234
```

#### Paso 2: Instalar Dependencias del Sistema
```bash
# Actualizar sistema
apt update && apt upgrade -y

# Instalar PHP 8.2 y extensiones necesarias
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl -y

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Instalar MySQL
apt install mysql-server -y
systemctl start mysql
systemctl enable mysql

# Instalar Nginx
apt install nginx -y

# Instalar Git
apt install git -y

# Instalar Supervisor para gestión de procesos
apt install supervisor -y
```

#### Paso 3: Configurar Base de Datos
```bash
# Configurar MySQL
mysql_secure_installation

# Crear base de datos
mysql -u root -p
CREATE DATABASE csdt_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'csdt_user'@'localhost' IDENTIFIED BY 'Control-1234';
GRANT ALL PRIVILEGES ON csdt_database.* TO 'csdt_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### Paso 4: Clonar y Configurar Backend
```bash
# Crear directorio para la aplicación
mkdir -p /var/www/csdt
cd /var/www/csdt

# Clonar repositorio
git clone https://github.com/veeduriacsdc-ui/backend-csdt.git backend
cd backend

# Instalar dependencias
composer install --optimize-autoloader --no-dev

# Configurar variables de entorno
cp .env.example .env
nano .env
```

#### Configuración del archivo .env en producción:
```env
APP_NAME="CSDT Sistema"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://104.248.212.204:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_database
DB_USERNAME=csdt_user
DB_PASSWORD=Control-1234

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=104.248.212.204,localhost:5173
```

#### Paso 5: Configurar Laravel
```bash
# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Cachear configuraciones
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chown -R www-data:www-data /var/www/csdt/backend
chmod -R 755 /var/www/csdt/backend
chmod -R 775 /var/www/csdt/backend/storage
chmod -R 775 /var/www/csdt/backend/bootstrap/cache
```

#### Paso 6: Configurar Supervisor
```bash
# Crear configuración de Supervisor
cat > /etc/supervisor/conf.d/csdt-backend.conf << EOF
[program:csdt-backend]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/csdt/backend/artisan serve --host=0.0.0.0 --port=8000
directory=/var/www/csdt/backend
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/csdt-backend.log
EOF

# Recargar Supervisor
supervisorctl reread
supervisorctl update
supervisorctl start csdt-backend
```

#### Paso 7: Configurar Nginx
```bash
# Crear configuración de Nginx
cat > /etc/nginx/sites-available/csdt-backend << EOF
server {
    listen 80;
    server_name 104.248.212.204;
    root /var/www/csdt/backend/public;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Habilitar sitio
ln -s /etc/nginx/sites-available/csdt-backend /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default

# Verificar configuración
nginx -t

# Reiniciar servicios
systemctl restart nginx
systemctl restart php8.2-fpm
systemctl enable nginx
systemctl enable php8.2-fpm
```

#### Paso 8: Configurar SSL (Opcional)
```bash
# Instalar Certbot
apt install certbot python3-certbot-nginx -y

# Obtener certificado SSL
certbot --nginx -d 104.248.212.204

# Verificar renovación automática
certbot renew --dry-run
```

#### Paso 9: Configurar Firewall
```bash
# Configurar UFW
ufw allow ssh
ufw allow 80
ufw allow 443
ufw allow 8000
ufw --force enable
```

## 📊 Base de Datos

### Tablas Principales

#### usuarios
- `id` - ID único
- `nom` - Nombres
- `ape` - Apellidos
- `email` - Correo electrónico
- `pass` - Contraseña (hash)
- `rol` - Rol (cli, ope, adm)
- `estado` - Estado (activo, inactivo, suspendido)
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

#### veedurias
- `id` - ID único
- `titulo` - Título de la veeduría
- `descripcion` - Descripción
- `tipo` - Tipo (social, ambiental, urbana, rural)
- `prioridad` - Prioridad (baja, media, alta)
- `estado` - Estado (pendiente, activa, completada, cancelada)
- `usuario_id` - ID del usuario que crea
- `operador_id` - ID del operador asignado
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

#### donaciones
- `id` - ID único
- `titulo` - Título de la donación
- `descripcion` - Descripción
- `tipo` - Tipo (monetaria, especie, servicios)
- `monto` - Monto (para donaciones monetarias)
- `estado` - Estado (pendiente, aprobada, rechazada)
- `usuario_id` - ID del usuario que dona
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

#### tareas
- `id` - ID único
- `titulo` - Título de la tarea
- `descripcion` - Descripción
- `prioridad` - Prioridad (baja, media, alta)
- `estado` - Estado (pendiente, en_progreso, completada, cancelada)
- `fecha_vencimiento` - Fecha de vencimiento
- `usuario_id` - ID del usuario asignado
- `created_at` - Fecha de creación
- `updated_at` - Fecha de actualización

### Relaciones
- Usuarios → Veedurías (1:N)
- Usuarios → Donaciones (1:N)
- Usuarios → Tareas (1:N)
- Roles → Usuarios (N:N)

## 🔌 API Endpoints

### Autenticación
```
POST   /api/login          # Iniciar sesión
POST   /api/registro       # Registro de usuarios
POST   /api/logout         # Cerrar sesión
GET    /api/usuario        # Información del usuario
```

### Usuarios
```
GET    /api/usuarios                    # Listar usuarios
POST   /api/usuarios                    # Crear usuario
GET    /api/usuarios/{id}               # Obtener usuario
PUT    /api/usuarios/{id}               # Actualizar usuario
DELETE /api/usuarios/{id}               # Eliminar usuario
PUT    /api/usuarios/{id}/estado        # Cambiar estado
POST   /api/usuarios/{id}/rol           # Asignar rol
```

### Veedurías
```
GET    /api/veedurias                           # Listar veedurías
POST   /api/veedurias                           # Crear veeduría
GET    /api/veedurias/{id}                      # Obtener veeduría
PUT    /api/veedurias/{id}                      # Actualizar veeduría
DELETE /api/veedurias/{id}                      # Eliminar veeduría
PUT    /api/veedurias/{id}/estado               # Cambiar estado
POST   /api/veedurias/{id}/asignar-operador     # Asignar operador
```

### Donaciones
```
GET    /api/donaciones              # Listar donaciones
POST   /api/donaciones              # Crear donación
GET    /api/donaciones/{id}         # Obtener donación
PUT    /api/donaciones/{id}         # Actualizar donación
DELETE /api/donaciones/{id}         # Eliminar donación
PUT    /api/donaciones/{id}/estado  # Cambiar estado
```

### Tareas
```
GET    /api/tareas                    # Listar tareas
POST   /api/tareas                    # Crear tarea
GET    /api/tareas/{id}               # Obtener tarea
PUT    /api/tareas/{id}               # Actualizar tarea
DELETE /api/tareas/{id}               # Eliminar tarea
PUT    /api/tareas/{id}/estado        # Cambiar estado
POST   /api/tareas/{id}/asignar-usuario # Asignar usuario
```

### Archivos
```
GET    /api/archivos              # Listar archivos
POST   /api/archivos              # Crear archivo
GET    /api/archivos/{id}         # Obtener archivo
PUT    /api/archivos/{id}         # Actualizar archivo
DELETE /api/archivos/{id}         # Eliminar archivo
POST   /api/archivos/{id}/descargar # Descargar archivo
```

### Roles
```
GET    /api/roles                           # Listar roles
POST   /api/roles                           # Crear rol
GET    /api/roles/{id}                      # Obtener rol
PUT    /api/roles/{id}                      # Actualizar rol
DELETE /api/roles/{id}                      # Eliminar rol
POST   /api/roles/{id}/asignar-permiso      # Asignar permiso
POST   /api/roles/{id}/revocar-permiso      # Revocar permiso
```

### Configuraciones
```
GET    /api/configuraciones                    # Listar configuraciones
POST   /api/configuraciones                    # Crear configuración
GET    /api/configuraciones/{id}               # Obtener configuración
PUT    /api/configuraciones/{id}               # Actualizar configuración
DELETE /api/configuraciones/{id}               # Eliminar configuración
GET    /api/configuraciones/clave/{clave}      # Obtener por clave
PUT    /api/configuraciones/clave/{clave}      # Actualizar por clave
```

### Logs
```
GET    /api/logs        # Listar logs
GET    /api/logs/{id}   # Obtener log
```

## 🔐 Autenticación

### Headers Requeridos
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Respuesta de Login
```json
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": {
      "id": 1,
      "nom": "Juan",
      "ape": "Pérez",
      "email": "juan@ejemplo.com",
      "rol": "cli",
      "estado": "activo"
    }
  }
}
```

## 📝 Validaciones

### Usuario
- `nom`: required|string|max:255
- `ape`: required|string|max:255
- `email`: required|email|unique:usuarios
- `pass`: required|string|min:8
- `rol`: required|in:cli,ope,adm

### Veeduría
- `titulo`: required|string|max:255
- `descripcion`: required|string
- `tipo`: required|in:social,ambiental,urbana,rural
- `prioridad`: required|in:baja,media,alta
- `usuario_id`: required|exists:usuarios,id

### Donación
- `titulo`: required|string|max:255
- `descripcion`: required|string
- `tipo`: required|in:monetaria,especie,servicios
- `monto`: required_if:tipo,monetaria|numeric|min:0
- `usuario_id`: required|exists:usuarios,id

## 🚀 Despliegue

### Producción
```bash
# Instalar dependencias de producción
composer install --optimize-autoloader --no-dev

# Cachear configuraciones
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones
php artisan migrate --force

# Configurar permisos
chmod -R 755 storage bootstrap/cache
```

### Variables de Entorno
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.csdt.com.co

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=csdt_production
DB_USERNAME=csdt_user
DB_PASSWORD=secure_password

SANCTUM_STATEFUL_DOMAINS=csdt.com.co
```

## 📊 Monitoreo

### Logs
- Logs de aplicación en `storage/logs/`
- Logs de base de datos en tabla `logs`
- Logs de autenticación
- Logs de errores

### Métricas
- Tiempo de respuesta de API
- Uso de memoria
- Consultas de base de datos
- Errores por endpoint

## 🔧 Mantenimiento

### Comandos Útiles
```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar autoloader
composer dump-autoload --optimize

# Verificar estado
php artisan about
php artisan route:list
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para la feature
3. Commit los cambios
4. Push a la rama
5. Abrir un Pull Request

## 📞 Soporte

Para soporte técnico:
- Email: soporte@csdt.com.co
- Documentación: https://docs.csdt.com.co
- Issues: GitHub Issues

## 🚀 Comandos de Despliegue Automático

### Script de Despliegue Completo
```bash
#!/bin/bash
# Script para desplegar automáticamente el sistema CSDT en DigitalOcean

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Iniciando despliegue del Sistema CSDT...${NC}"

# Actualizar sistema
echo -e "${YELLOW}📦 Actualizando sistema...${NC}"
apt update && apt upgrade -y

# Instalar dependencias
echo -e "${YELLOW}📦 Instalando dependencias...${NC}"
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-bcmath php8.2-gd php8.2-intl nginx mysql-server git supervisor -y

# Instalar Composer
echo -e "${YELLOW}📦 Instalando Composer...${NC}"
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Configurar MySQL
echo -e "${YELLOW}🗄️ Configurando MySQL...${NC}"
systemctl start mysql
systemctl enable mysql

# Crear base de datos
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS csdt_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'csdt_user'@'localhost' IDENTIFIED BY 'Control-1234';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON csdt_database.* TO 'csdt_user'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"

# Crear directorio de la aplicación
echo -e "${YELLOW}📁 Creando directorio de la aplicación...${NC}"
mkdir -p /var/www/csdt
cd /var/www/csdt

# Clonar repositorios
echo -e "${YELLOW}📥 Clonando repositorios...${NC}"
git clone https://github.com/veeduriacsdc-ui/backend-csdt.git backend
git clone https://github.com/veeduriacsdc-ui/frontend-csdt.git frontend

# Configurar Backend
echo -e "${YELLOW}⚙️ Configurando Backend...${NC}"
cd backend
composer install --optimize-autoloader --no-dev
cp .env.example .env

# Configurar .env del backend
cat > .env << EOF
APP_NAME="CSDT Sistema"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://104.248.212.204:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=csdt_database
DB_USERNAME=csdt_user
DB_PASSWORD=Control-1234

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

SANCTUM_STATEFUL_DOMAINS=104.248.212.204,localhost:5173
EOF

php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar Frontend
echo -e "${YELLOW}⚙️ Configurando Frontend...${NC}"
cd ../frontend
npm install
cp .env.example .env

# Configurar .env del frontend
cat > .env << EOF
VITE_API_URL=http://104.248.212.204:8000/api
VITE_APP_NAME="CSDT Sistema"
VITE_APP_VERSION="1.0.0"
VITE_APP_ENV=production
EOF

npm run build

# Configurar permisos
echo -e "${YELLOW}🔐 Configurando permisos...${NC}"
chown -R www-data:www-data /var/www/csdt
chmod -R 755 /var/www/csdt
chmod -R 775 /var/www/csdt/backend/storage
chmod -R 775 /var/www/csdt/backend/bootstrap/cache

# Configurar Supervisor
echo -e "${YELLOW}⚙️ Configurando Supervisor...${NC}"
cat > /etc/supervisor/conf.d/csdt-backend.conf << EOF
[program:csdt-backend]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/csdt/backend/artisan serve --host=0.0.0.0 --port=8000
directory=/var/www/csdt/backend
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/supervisor/csdt-backend.log
EOF

supervisorctl reread
supervisorctl update
supervisorctl start csdt-backend

# Configurar Nginx
echo -e "${YELLOW}⚙️ Configurando Nginx...${NC}"
cat > /etc/nginx/sites-available/csdt << EOF
server {
    listen 80;
    server_name 104.248.212.204;
    root /var/www/csdt/frontend/dist;
    index index.html;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api {
        proxy_pass http://104.248.212.204:8000;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

ln -s /etc/nginx/sites-available/csdt /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl restart nginx
systemctl enable nginx

# Configurar Firewall
echo -e "${YELLOW}🔒 Configurando Firewall...${NC}"
ufw allow ssh
ufw allow 80
ufw allow 443
ufw allow 8000
ufw --force enable

echo -e "${GREEN}✅ Despliegue completado exitosamente!${NC}"
echo -e "${GREEN}🌐 Frontend disponible en: http://104.248.212.204${NC}"
echo -e "${GREEN}🔧 Backend disponible en: http://104.248.212.204:8000${NC}"
echo -e "${GREEN}📧 Email: veeduriacsdc@gmail.com${NC}"
echo -e "${GREEN}🔑 Contraseña: Control-1234${NC}"
```

### Comandos de Mantenimiento
```bash
# Verificar estado de los servicios
systemctl status nginx
systemctl status mysql
supervisorctl status

# Ver logs
tail -f /var/log/supervisor/csdt-backend.log
tail -f /var/log/nginx/error.log

# Reiniciar servicios
systemctl restart nginx
supervisorctl restart csdt-backend

# Actualizar código
cd /var/www/csdt/backend
git pull origin main
composer install --optimize-autoloader --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd /var/www/csdt/frontend
git pull origin main
npm install
npm run build
```

## 📄 Licencia

Este proyecto está bajo la Licencia MIT.