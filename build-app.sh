#!/bin/bash
set -e

# 1) Instala dependencias PHP/Composer
composer install --no-dev --optimize-autoloader
# 2) Enlaza storage
php artisan storage:link
# 4) Opcache/configuración óptima (opcional)
php artisan config:cache
php artisan route:cache
php artisan view:cache
