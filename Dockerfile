FROM php:8.4-cli-bookworm AS vendor

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-install \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

COPY . .

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

FROM node:22-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN npm run build

FROM php:8.4-cli-bookworm

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

WORKDIR /var/www/html

COPY docker/php/local.ini /usr/local/etc/php/conf.d/99-barcodeos.ini
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
        bootstrap/cache \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R ${APP_USER}:${APP_GROUP} bootstrap/cache storage

EXPOSE 3000

CMD ["sh", "-lc", "mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs && chown -R www-data:www-data bootstrap/cache storage && if [ -z \"${APP_KEY:-}\" ]; then export APP_KEY=\"base64:$(php -r 'echo base64_encode(random_bytes(32));')\"; echo 'APP_KEY is missing in environment; using an in-memory fallback key for this container boot. Configure a persistent APP_KEY in Coolify for production.'; fi && php artisan package:discover --ansi && php artisan config:cache && php artisan migrate --force && exec php artisan serve --host=0.0.0.0 --port=3000"]
