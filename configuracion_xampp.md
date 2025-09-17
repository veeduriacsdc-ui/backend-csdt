# CONFIGURACIÓN XAMPP PARA CSDT

## 1. Configuración de Base de Datos

### Crear Base de Datos
```sql
CREATE DATABASE csdt_veeduria CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Configuración .env
Crear archivo `.env` en la raíz del proyecto con:

```env
APP_NAME="CONSEJO SOCIAL DE VEEDURÍA Y DESARROLLO TERRITORIAL"
APP_ENV=local
APP_KEY=base64:YourAppKeyHere
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
QUEUE_CONNECTION=database
```

## 2. Comandos de Instalación

```bash
# Instalar dependencias
composer install
npm install

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Crear usuario administrador
php artisan db:seed
```

## 3. Configuración Apache

### Virtual Host
```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/backend-csdt/public"
    ServerName csdt.local
    <Directory "C:/xampp/htdocs/backend-csdt/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 4. Configuración PHP

### php.ini
```ini
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300
memory_limit = 512M
```

## 5. Verificación

1. Iniciar XAMPP (Apache + MySQL)
2. Acceder a http://localhost/backend-csdt/public
3. Verificar que la aplicación cargue correctamente
