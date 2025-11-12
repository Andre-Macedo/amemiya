# STAGE 1: Builder - Instala todas as dependências de build e compila os assets
FROM php:8.2-fpm AS builder

# Evita perguntas interativas durante a instalação de pacotes
ENV DEBIAN_FRONTEND=noninteractive

# Instala dependências do sistema + Node.js
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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache sodium

# Instala Node.js v20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Instala o Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Instala dependências do PHP e Node (em camadas para otimizar o cache)
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json* ./
RUN npm install

# Copia o restante do código da aplicação
COPY . .

ENV COMPOSER_ALLOW_SUPERUSER=1
# Gera o autoloader e compila os assets
RUN composer dump-autoload --optimize
RUN npm run build


# STAGE 2: App - A imagem final, mais leve e pronta para produção/desenvolvimento
FROM php:8.2-fpm AS app

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Instala apenas as extensões PHP necessárias para rodar a aplicação
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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache sodium

# Copia o composer para podermos usar `docker-compose exec`
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY .docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache-custom.ini

WORKDIR /var/www/html

# Copia os arquivos da aplicação já "buildados" do stage anterior
COPY --from=builder /var/www/html .

# Ajusta permissões para o usuário do PHP-FPM
RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
