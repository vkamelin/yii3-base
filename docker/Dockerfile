FROM composer/composer:2-bin AS composer

FROM php:8.4-fpm-bookworm AS base

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        supervisor \
        libzip-dev \
        libicu-dev \
        libpq-dev \
        libonig-dev \
        libxml2-dev \
        libssl-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        intl \
        bcmath \
        pcntl \
        sockets \
        opcache \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /composer /usr/bin/composer

WORKDIR /app

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/conf.d/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/php-fpm/zz-app.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/supervisor/conf.d /etc/supervisor/conf.d
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /app/runtime /app/runtime/logs /app/runtime/cache \
    && chown -R www-data:www-data /app/runtime

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

FROM base AS dev

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN set -eux; \
    if [ "${GROUP_ID}" != "33" ]; then groupmod -o -g "${GROUP_ID}" www-data; fi; \
    if [ "${USER_ID}" != "33" ]; then usermod -o -u "${USER_ID}" www-data; fi

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

FROM base AS prod-builder
COPY .. /app
RUN --mount=type=cache,target=/tmp/cache \
    composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    && rm -rf /root/.composer/cache

FROM base AS prod
ENV APP_ENV=prod
COPY --from=prod-builder --chown=www-data:www-data /app /app
