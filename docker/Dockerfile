FROM php:8.2-fpm

ENV COMPOSER_HOME /tmp

RUN apt-get update

RUN apt-get install -y \
    git \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    locales \
    unzip

RUN docker-php-ext-configure intl

RUN docker-php-ext-install -j$(nproc) intl pcntl pdo pdo_pgsql zip

RUN echo 'en_GB.UTF-8 UTF-8' >> /etc/locale.gen && \
    echo 'fr_FR.UTF-8 UTF-8' >> /etc/locale.gen && \
    locale-gen

COPY php.ini $PHP_INI_DIR/conf.d/webapp.ini

COPY --from=composer/composer /usr/bin/composer /usr/bin/composer
