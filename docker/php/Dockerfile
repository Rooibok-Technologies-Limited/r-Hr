FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libicu-dev libcurl4-openssl-dev libonig-dev \
    libzip-dev libpq-dev unzip git curl \
  && docker-php-ext-install \
    intl curl mbstring pdo pdo_pgsql pgsql zip \
  && pecl install redis \
  && docker-php-ext-enable redis \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN chown -R www-data:www-data /var/www/html
