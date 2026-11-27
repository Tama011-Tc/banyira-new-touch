# Production image for hosting Banyira (used by render.yaml).
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

FROM node:24-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.ts tsconfig.json ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

FROM php:8.4-apache
# Dump programs used by database backups.
RUN apt-get update     && apt-get install -y --no-install-recommends default-mysql-client postgresql-client     && rm -rf /var/lib/apt/lists/*
ENV BACKUP_MYSQLDUMP_PATH=mariadb-dump
RUN docker-php-ext-install pdo_mysql bcmath opcache \
    && a2enmod rewrite \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n AllowOverride All\n Require all granted\n</Directory>\n' > /etc/apache2/conf-available/app.conf \
    && a2enconf app
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=vendor /usr/bin/composer /usr/bin/composer
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/private/documents bootstrap/cache \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && sed -i 's/\r$//' docker/start.sh
CMD ["sh", "docker/start.sh"]
