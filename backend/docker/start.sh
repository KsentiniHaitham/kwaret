#!/bin/sh
set -e

echo "==> Starting Kwaret backend..."

# Default port
export PORT=${PORT:-8080}

# Generate nginx config from template (substitutes $PORT)
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Generate JWT keys
echo "==> Generating JWT keys..."
mkdir -p /var/www/config/jwt
openssl genpkey -algorithm RSA \
    -out /var/www/config/jwt/private.pem \
    -pkeyopt rsa_keygen_bits:4096 \
    -pass pass:${JWT_PASSPHRASE} 2>/dev/null
openssl pkey \
    -in /var/www/config/jwt/private.pem \
    -out /var/www/config/jwt/public.pem \
    -pubout \
    -passin pass:${JWT_PASSPHRASE} 2>/dev/null
chown www-data:www-data /var/www/config/jwt/*.pem
echo "==> JWT keys generated."

# Clear and warm up Symfony cache (now we have all env vars)
echo "==> Warming up Symfony cache..."
cd /var/www
php bin/console cache:clear --env=prod --no-warmup 2>&1 || true
php bin/console cache:warmup --env=prod 2>&1 || true

# Wait for MySQL (up to 30s)
echo "==> Waiting for database..."
for i in $(seq 1 15); do
    php -r "new PDO(getenv('DATABASE_URL'), null, null, [PDO::ATTR_TIMEOUT=>2]);" 2>/dev/null && break || true
    echo "   DB not ready ($i/15), retrying in 2s..."
    sleep 2
done

# Run schema update
echo "==> Running database schema update..."
php bin/console doctrine:schema:update --force --complete --env=prod 2>&1 || echo "Schema already up to date."

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
