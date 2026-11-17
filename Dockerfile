# syntax=docker/dockerfile:1

##########################################################################
# Stage 1 — Build front-end assets (Vite / Tailwind CSS)
##########################################################################
FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

##########################################################################
# Stage 2 — Production runtime (PHP-FPM + NGINX)
##########################################################################
FROM serversideup/php:8.3-fpm-nginx AS runtime

USER root

# Extensiones requeridas por Laravel y barryvdh/laravel-dompdf.
RUN install-php-extensions pdo_mysql gd intl zip

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

ENV AUTORUN_ENABLED=false \
    PHP_OPCACHE_ENABLE=1

USER www-data

EXPOSE 8080
