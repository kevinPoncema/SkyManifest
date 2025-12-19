FROM php:8.2-fpm

# Argumentos para el usuario del sistema (útil para coincidir con tu host en Linux)
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
# Usamos el usuario www-data pero ajustamos su UID si es necesario
# Esto asegura que Laravel pueda escribir en storage y en el volumen de sitios
RUN usermod -u $uid $user || true

# 5. Carpeta de trabajo
WORKDIR /var/www/html

# 6. Cambiar usuario actual
USER $user

# Exponemos el puerto 9000 (FPM)
EXPOSE 9000

CMD ["php-fpm"]
