FROM node:22-bookworm-slim AS node

FROM composer:2.8 AS composer

FROM php:8.4-fpm-bookworm

ARG APP_USER=www-data
ARG APP_GROUP=www-data

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libicu-dev \
        libpq-dev \
        libsqlite3-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        pdo_sqlite \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=node /usr/local/ /usr/local/

WORKDIR /var/www/html

COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-barcodeos.ini

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R ${APP_USER}:${APP_GROUP} /var/www/html

CMD ["php-fpm"]
