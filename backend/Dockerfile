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

# Allow composer plugins to run as root (needed for symfony/flex & symfony/runtime)
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www

# Copy everything first
COPY . .

# Install dependencies WITH scripts so symfony/runtime is properly bootstrapped
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Directories & permissions
RUN mkdir -p public/uploads/products var/cache var/log \
    && chown -R www-data:www-data var public

# Nginx template & startup script
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/start.sh /start.sh
RUN sed -i 's/\r//' /start.sh && chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
