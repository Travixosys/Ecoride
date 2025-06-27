# Stage 1: install Composer deps
FROM composer:2 AS vendor
WORKDIR /var/www

# copy only Composer files & install
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Stage 2: PHP runtime
FROM php:8.2-fpm-alpine
RUN apk add --no-cache \
      pcre-dev \
      oniguruma-dev \
      libzip-dev \
      curl \
    && docker-php-ext-install pdo pdo_mysql zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

WORKDIR /var/www
# copy vendor and app code
COPY --from=vendor /var/www/vendor ./vendor
COPY app        ./app
COPY public     ./public
COPY data       ./data

EXPOSE 9000
CMD ["php-fpm", "-F"]
