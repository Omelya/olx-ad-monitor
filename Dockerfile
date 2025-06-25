FROM composer:2.1 as build
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs
COPY . .

FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    zip \
    unzip

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    gd \
    exif \
    pcntl \
    bcmath

WORKDIR /app

COPY --from=build /app .

RUN mkdir -p logs && chmod 777 logs

RUN chown -R www-data:www-data /app

USER www-data

CMD ["php", "bin/monitor-daemon"]
