FROM php:8.3-fpm-alpine

# System deps
RUN apk add --no-cache \
    nginx \
    curl \
    unzip \
    git \
    openssl \
    gettext \
    && docker-php-ext-install pdo pdo_mysql opcache

# PHP opcache
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Install PHP deps (layer cache)
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy app
COPY . .

# Clear Symfony cache
RUN APP_ENV=prod php bin/console cache:clear --no-warmup --env=prod \
    && APP_ENV=prod php bin/console cache:warmup --env=prod

# Directories & permissions
RUN mkdir -p public/uploads/products var/cache var/log \
    && chown -R www-data:www-data var public

# Copy nginx template & startup script
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
