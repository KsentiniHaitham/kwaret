#!/bin/sh
set -e

echo "==> Starting Kwaret backend..."

# Default port
export PORT=${PORT:-8080}

# Generate nginx config from template
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Generate JWT keys (RSA 2048 — much faster than 4096)
echo "==> Generating JWT keys..."
mkdir -p /var/www/config/jwt
openssl genrsa -out /var/www/config/jwt/private.pem \
    -passout pass:${JWT_PASSPHRASE} 2048 2>/dev/null
openssl rsa -in /var/www/config/jwt/private.pem \
    -out /var/www/config/jwt/public.pem \
    -pubout \
    -passin pass:${JWT_PASSPHRASE} 2>/dev/null
chown www-data:www-data /var/www/config/jwt/*.pem
echo "==> JWT keys generated."

cd /var/www

# Clear Symfony cache
echo "==> Clearing cache..."
php bin/console cache:clear --env=prod --no-warmup 2>&1 || true
php bin/console cache:warmup --env=prod 2>&1 || true

# Wait for MySQL (up to 60s)
echo "==> Waiting for database..."
for i in $(seq 1 30); do
    php bin/console doctrine:query:sql "SELECT 1" --env=prod 2>/dev/null && break || true
    echo "   DB not ready ($i/30), retrying in 2s..."
    sleep 2
done

# Update schema
echo "==> Updating database schema..."
php bin/console doctrine:schema:update --force --complete --env=prod 2>&1 || echo "Schema OK."

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
