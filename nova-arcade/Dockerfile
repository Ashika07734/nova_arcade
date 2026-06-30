FROM php:8.2-cli-bookworm AS base

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libonig-dev \
    && docker-php-ext-install pdo_pgsql pgsql bcmath intl zip opcache mbstring xml curl pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM node:20-bookworm-slim AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM base AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN php artisan package:discover --ansi

FROM base AS production

WORKDIR /var/www/html

COPY --from=vendor /var/www/html /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "10000"]