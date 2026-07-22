FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache \
    bash git curl icu-dev libzip-dev oniguruma-dev postgresql-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev $PHPIZE_DEPS linux-headers

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo pdo_pgsql pgsql mbstring bcmath exif pcntl gd zip intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP config
RUN { \
      echo "upload_max_filesize=25M"; \
      echo "post_max_size=30M"; \
      echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/app.ini

# Run fpm workers as root (single-tenant self-hosted; needed for Windows bind mounts
# where chown/chmod are no-ops and files appear root-owned)
RUN sed -i 's/^user = www-data/user = root/; s/^group = www-data/group = root/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000
# -R allows fpm pool to run as root (see www.conf tweak above; single-tenant self-hosted)
CMD ["php-fpm", "-R"]
