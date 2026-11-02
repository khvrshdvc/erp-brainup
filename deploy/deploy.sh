#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan pulse:restart
sudo systemctl reload php8.3-fpm

curl -sS -o /dev/null -w 'https://brain-up.uz/login: %{http_code} in %{time_total}s\n' https://brain-up.uz/login
