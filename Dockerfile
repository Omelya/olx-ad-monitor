FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json ./
COPY composer.lock ./

RUN composer install --no-dev --optimize-autoloader

COPY . .

RUN mkdir -p logs && chmod 777 logs

RUN chown -R www-data:www-data /app

USER www-data

CMD ["php", "bin/monitor-daemon"]
