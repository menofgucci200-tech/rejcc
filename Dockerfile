# --- Etape 1 : build des assets front (Vite/Tailwind) ---
FROM node:20-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm install
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

# --- Etape 2 : application PHP/Laravel ---
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .
COPY --from=assets /app/public/build ./public/build

# Laravel a besoin de ces dossiers (memes vides) pour ecrire son cache
# au moment du composer install (package:discover).
RUN mkdir -p bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chmod -R 775 bootstrap/cache storage

RUN composer install --no-dev --optimize-autoloader --no-interaction

EXPOSE 10000

CMD sh -c "php artisan storage:link || true; php artisan migrate --force; php artisan serve --host 0.0.0.0 --port ${PORT:-10000}"
