FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libxml2-dev \
       libsqlite3-dev \
       zip \
       unzip \
       git \
    && docker-php-ext-install pdo pdo_sqlite \
    && a2enmod rewrite

# composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 80
