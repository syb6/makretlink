#!/bin/bash
# Railway pre-deploy: migrate, seed (idempotent), link storage, cache config.
# Make executable: chmod +x railway/init-app.sh
set -e

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding database (idempotent — safe on every deploy)..."
php artisan db:seed --force

echo "==> Ensuring public/storage symlink..."
php artisan storage:link || true

echo "==> Rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "==> Init complete."
