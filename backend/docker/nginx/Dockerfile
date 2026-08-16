FROM node:22-alpine AS frontend-build

WORKDIR /app/frontend

ARG ANGULAR_NODE_OPTIONS="--max-old-space-size=1536"
ENV NODE_OPTIONS="${ANGULAR_NODE_OPTIONS}" \
    NG_BUILD_MAX_WORKERS=1

COPY frontend/package*.json ./
RUN npm ci

COPY frontend/ ./
RUN npm run build -- --configuration production --progress=false

FROM php:8.3-cli-bookworm AS backend-vendor

WORKDIR /app/backend

RUN apt-get update \
    && apt-get install -y --no-install-recommends git libpng-dev libzip-dev unzip \
    && docker-php-ext-install gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader

FROM node:22-alpine AS backend-assets

WORKDIR /app/backend

COPY backend/package*.json ./
RUN npm ci

COPY --from=backend-vendor /app/backend/vendor ./vendor
COPY backend/vite.config.js ./
COPY backend/resources ./resources
COPY backend/public ./public
RUN npm run build

FROM nginx:1.27-alpine

RUN apk add --no-cache openssl

COPY backend/docker/nginx/production.conf /etc/nginx/conf.d/default.conf
COPY backend/docker/nginx/entrypoint.sh /docker-entrypoint.d/10-lg-ssl-bootstrap.sh
COPY --from=frontend-build /app/frontend/dist/frontend/browser /usr/share/nginx/html
COPY --from=backend-assets /app/backend/public/build /var/www/backend-public/build
COPY backend/public/images /var/www/backend-public/images
COPY backend/public/robots.txt /var/www/backend-public/robots.txt

RUN chmod +x /docker-entrypoint.d/10-lg-ssl-bootstrap.sh
