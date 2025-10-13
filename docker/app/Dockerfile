# syntax=docker/dockerfile:1.6

ARG PHP_VERSION=8.2
ARG NODE_VERSION=18
ARG ALPINE_VERSION=3.20

FROM composer:2 AS composer_builder
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist

FROM node:${NODE_VERSION}-alpine AS frontend_builder
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=composer_builder /var/www/html /var/www/html
RUN npm run build

FROM php:${PHP_VERSION}-fpm-alpine${ALPINE_VERSION} AS runtime

RUN set -eux; \
    apk add --no-cache \
        bash \
        curl \
        fcgi \
        icu-data-full \
        icu-libs \
        libzip \
        supervisor \
        tzdata && \
    apk add --no-cache --virtual .build-deps \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        autoconf \
        make \
        g++ \
        linux-headers \
        openssl-dev && \
    docker-php-ext-install \
        intl \
        pdo_mysql \
        zip && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del .build-deps

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_OPCACHE_MAX_ACCELERATED_FILES=20000 \
    PHP_OPCACHE_MEMORY_CONSUMPTION=256 \
    PHP_OPCACHE_INTERNED_STRINGS_BUFFER=32

WORKDIR /var/www/html

COPY --from=composer_builder /usr/bin/composer /usr/bin/composer
COPY --from=composer_builder /var/www/html/vendor /var/www/html/vendor
COPY --from=composer_builder /var/www/html/composer.json /var/www/html/composer.json
COPY --from=composer_builder /var/www/html/composer.lock /var/www/html/composer.lock
COPY --from/frontend_builder /var/www/html/public/build /var/www/html/public/build
COPY . /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R ug+rwx storage bootstrap/cache

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000
USER www-data
ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
