#!/bin/sh
set -e

# Render supplies a random key without Laravel's prefix, and the public address.
case "$APP_KEY" in base64:*) ;; *) export APP_KEY="base64:$APP_KEY" ;; esac
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:$PORT>/" /etc/apache2/sites-available/000-default.conf

php artisan config:cache
php artisan migrate --force
php artisan banyira:seed-if-empty
php artisan route:cache
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache

# Overdue flags, offer expiry and reminders. Runs only while the service is awake.
su -s /bin/sh www-data -c "php artisan schedule:work" &

exec apache2-foreground
