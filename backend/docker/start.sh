#!/bin/sh
set -e

echo "==> Starting Kwaret backend..."

# Default port
export PORT=${PORT:-8080}

# Generate nginx config from template (substitutes $PORT)
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Generate JWT keys with the passphrase from env
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

# Wait for MySQL to be ready (up to 30s)
echo "==> Waiting for database..."
for i in $(seq 1 15); do
    php -r "
        try {
            new PDO(getenv('DATABASE_URL') ?: 'mysql://root@127.0.0.1/kwaret', null, null, [PDO::ATTR_TIMEOUT => 2]);
            exit(0);
        } catch (Exception \$e) {
            exit(1);
        }
    " 2>/dev/null && break
    echo "   DB not ready, retrying in 2s... ($i/15)"
    sleep 2
done

# Run schema update
echo "==> Running database schema update..."
cd /var/www
APP_ENV=prod php bin/console doctrine:schema:update --force --complete 2>&1 || echo "Schema update done (or already up to date)"

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx on port ${PORT}..."
exec nginx -g 'daemon off;'
