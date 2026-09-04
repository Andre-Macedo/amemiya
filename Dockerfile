# STAGE 1: Builder
FROM php:8.4-fpm AS builder

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libsodium-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim unzip git curl \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && pecl install redis \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache sodium \
    && docker-php-ext-enable redis

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json* ./
RUN npm install
COPY . .
RUN composer dump-autoload --optimize
RUN npm run build

# STAGE 2: App
FROM php:8.4-fpm AS app

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libsodium-dev \
    zip \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && pecl install redis \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache sodium \
    && docker-php-ext-enable redis

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer
COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache-custom.ini

WORKDIR /var/www/html
COPY --from=builder /var/www/html .
RUN chown -R www-data:www-data /var/www/html

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
