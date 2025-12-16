#!/usr/bin/env bash
set -e

cd /var/www/mkl

echo "Fixing permissions..."
chown -R www-data:www-data storage bootstrap/cache

find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;

find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

echo "Clearing file sessions..."
rm -f storage/framework/sessions/* || true

echo "Clearing Laravel cache..."
sudo -u www-data php artisan optimize:clear

echo "Restarting PHP-FPM..."
systemctl restart php8.1-fpm

echo "Post-deploy done."
