# === CSDT BACKEND DOCKERFILE ===
FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    mysql-client \
    redis \
    git \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        xml \
        intl \
        opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de configuración
COPY composer.json composer.lock ./
COPY .env.production .env

# Instalar dependencias de PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar código fuente
COPY . .

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configurar PHP para producción
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/memory_limit = 128M/memory_limit = 512M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/max_execution_time = 30/max_execution_time = 300/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 50M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = 8M/post_max_size = 50M/' "$PHP_INI_DIR/php.ini"

# Configurar OPcache para producción
RUN echo 'opcache.enable=1' >> "$PHP_INI_DIR/php.ini" \
    && echo 'opcache.memory_consumption=256' >> "$PHP_INI_DIR/php.ini" \
    && echo 'opcache.max_accelerated_files=7963' >> "$PHP_INI_DIR/php.ini" \
    && echo 'opcache.revalidate_freq=0' >> "$PHP_INI_DIR/php.ini" \
    && echo 'opcache.validate_timestamps=0' >> "$PHP_INI_DIR/php.ini"

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/default.conf /etc/nginx/conf.d/default.conf

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Generar clave de aplicación si no existe
RUN php artisan key:generate --no-interaction || true

# Cache de configuración y rutas
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Crear enlace simbólico para storage
RUN php artisan storage:link || true

# Exponer puerto
EXPOSE 80

# Comando de inicio
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1