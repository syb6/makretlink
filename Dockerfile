FROM node:22.22.3-bookworm-slim AS frontend-build

WORKDIR /app/frontend

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ ./
COPY public/css/app.css /app/public/css/app.css
RUN npm run build

FROM php:8.3-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends libzip-dev unzip \
    && docker-php-ext-install pdo_mysql zip \
    && apt-get purge -y --auto-remove libzip-dev \
    && apt-get install -y --no-install-recommends libzip4 \
    && php -r 'exit(extension_loaded("pdo_mysql") && extension_loaded("zip") ? 0 : 1);' \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .
COPY --from=frontend-build /app/public/spa ./public/spa

RUN mkdir -p database storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-progress \
    --optimize-autoloader \
    && chmod -R ug+rwX storage bootstrap/cache

CMD ["sh", "-c", "php artisan storage:link --force && php artisan optimize && exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
