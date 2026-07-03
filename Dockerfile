FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
        git unzip curl libzip-dev libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring zip exif pcntl gd \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run build

EXPOSE 10000

CMD sh -c "php artisan storage:link || true; php artisan migrate --force; php artisan serve --host 0.0.0.0 --port ${PORT:-10000}"
