# syntax=docker/dockerfile:1

# ---------- Stage 1: build frontend assets (Tailwind + Alpine.js via Vite) ----------
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js tailwind.config.js postcss.config.js ./
RUN npm run build

# ---------- Stage 2: PHP application (Composer deps + kode aplikasi) ----------
FROM php:8.2-fpm AS php_base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libicu-dev \
        default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/pear
# default-mysql-client: mysqldump untuk `php artisan app:backup-database`.
# redis: cache cepat (CACHE_STORE=failover, CACHE_FAILOVER_STORES=redis,database).

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Lapisan composer install dipisah dari COPY kode supaya cache Docker tidak
# batal setiap kali ada perubahan kode yang tidak menyentuh dependency.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
COPY --from=node_builder /app/public/build ./public/build

RUN composer dump-autoload --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---------- Stage 3: Nginx, menyajikan public/ hasil build di atas ----------
FROM nginx:alpine AS nginx_server
COPY docker/nginx/app.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/fallback.html /usr/share/nginx/fallback/fallback.html
COPY --from=php_base /var/www/html/public /var/www/html/public
EXPOSE 80
