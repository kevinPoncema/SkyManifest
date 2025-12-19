FROM php:8.2-fpm

# Argumentos para el usuario del sistema
ARG user=www-data
ARG uid=1000

# 1. Instalar dependencias del sistema y librerías necesarias
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 2. Instalar Node.js y npm (Versión 18.x LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# 3. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configuración de usuario para evitar problemas de permisos
RUN usermod -u $uid $user || true

# 5. Carpeta de trabajo de la aplicación
WORKDIR /var/www/html

# 5.5. Crear el directorio de despliegues y asignar permisos
RUN mkdir -p /var/www/sites && \
    chown -R $user:$user /var/www/sites

# 6. Cambiar al usuario sin privilegios
USER $user

EXPOSE 9000

CMD ["php-fpm"]