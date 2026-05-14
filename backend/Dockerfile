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

# PHP opcache config for production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first (layer caching)
COPY composer.json composer.lock symfony.lock ./

# Install PHP dependencies (skip scripts, app code not copied yet)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy the rest of the application
COPY . .

# Clear and warm Symfony cache
RUN APP_ENV=prod php bin/console cache:clear --no-warmup \
    && APP_ENV=prod php bin/console cache:warmup

# Create uploads directory
RUN mkdir -p public/uploads/products \
    && chown -R www-data:www-data var public

# Copy config files
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
