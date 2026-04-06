FROM php:8.2-apache

# 1. Instalamos dependencias del sistema necesarias para las extensiones de PHP
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 2. Instalamos las extensiones de PHP (ahora sí funcionará mbstring)
RUN docker-php-ext-install pdo pdo_mysql mbstring opcache

# 3. Instalamos Xdebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# 4. Traemos Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 5. Copiamos los archivos del proyecto
COPY . .

RUN if [ -f composer.json ]; then composer install --no-interaction --no-dev 2>/dev/null || true; fi


COPY config/apache.conf /etc/apache2/sites-available/000-default.conf

# 7. Habilitamos el módulo de reescritura de Apache (útil para frameworks)
RUN a2enmod rewrite
