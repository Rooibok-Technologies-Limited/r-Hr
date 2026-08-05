FROM php:8.2-fpm

# Install dependencies + security updates. `gosu` lets the root entrypoint drop
# privileges to the app uid after fixing volume ownership.
RUN apt-get update && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
    libicu-dev libcurl4-openssl-dev libonig-dev \
    libzip-dev libpq-dev unzip git curl gosu \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
    intl curl mbstring pdo pdo_pgsql pgsql zip gd \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apt-get purge -y --auto-remove git \
  && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Remap www-data to uid/gid 1000 so php-fpm workers match the host bind-mount
# owner and can write writable/ (sessions, cache, logs) without a 500.
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data || true

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# One-command boot: entrypoint migrates + seeds, then runs the CMD.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
