# syntax=docker/dockerfile:1
#
# Dockerfile khusus PaaS satu-container (Railway, Render, Fly.io, dll).
# Berbeda dari Dockerfile utama di root repo: di sana Nginx dan PHP-FPM
# adalah dua image/container terpisah (cocok untuk docker-compose / IaaS
# manual). Kebanyakan platform PaaS hanya menjalankan SATU container per
# service, jadi Nginx dan PHP-FPM harus hidup bersama di dalam container
# yang sama.

FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

FROM php:8.2-fpm AS paas

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=node_builder /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/paas/nginx.conf.template /etc/nginx/conf.d/app.conf.template
RUN rm -f /etc/nginx/sites-enabled/default

COPY docker/paas/start.sh /usr/local/bin/start.sh
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/start.sh /usr/local/bin/entrypoint.sh

EXPOSE 8080
ENTRYPOINT ["entrypoint.sh"]
CMD ["start.sh"]
