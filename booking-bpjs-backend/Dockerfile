FROM php:8.2-fpm

# Install system deps
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 🔥 COPY composer dulu (INI KUNCI)
COPY composer.json composer.lock ./

# 🔥 install dependency TANPA vendor lama
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# baru copy source code
COPY . .

# permission
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www

EXPOSE 9000
CMD ["php-fpm"]