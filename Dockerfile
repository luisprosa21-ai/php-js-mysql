FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mbstring opcache
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN if [ -f composer.json ]; then composer install --no-interaction --no-dev 2>/dev/null || true; fi

RUN a2enmod rewrite
