FROM php:8.2-fpm

# Install dependencies + security updates
RUN apt-get update && apt-get upgrade -y \
  && apt-get install -y --no-install-recommends \
    libicu-dev libcurl4-openssl-dev libonig-dev \
    libzip-dev libpq-dev unzip git curl \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install \
    intl curl mbstring pdo pdo_pgsql pgsql zip gd \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apt-get purge -y --auto-remove git \
  && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN chown -R www-data:www-data /var/www/html
