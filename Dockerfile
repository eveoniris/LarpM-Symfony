# Base layer with FrankenPHP and PHP extensions
FROM dunglas/frankenphp:1-php8.4-trixie AS base
WORKDIR /app

RUN install-php-extensions \
    pdo_mysql \
    intl \
    zip \
    sockets \
    calendar \
    exif \
    gd \
    xsl \
    mysqli \
    apcu
## NOTE: mbstring and opcache are present in FrankenPHP base image

# Build layer with Composer
FROM composer:2 AS build
WORKDIR /build

COPY composer.json composer.lock ./
RUN APP_ENV=prod composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-req=ext-*

COPY . .
RUN APP_ENV=prod composer dump-autoload --classmap-authoritative --no-dev

# ---

# Production layer
FROM base AS prod
ENV APP_ENV=prod \
    APP_DEBUG=0

ARG USER=appuser

RUN useradd ${USER} \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown ${USER}:${USER} /app \
    && chown -R ${USER}:${USER} /config/caddy /data/caddy

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zzz-app.ini
COPY --from=build --chown=${USER}:${USER} /build /app

USER ${USER}

RUN DATABASE_URL="sqlite:///:memory:" php bin/console cache:clear --no-warmup \
    && DATABASE_URL="sqlite:///:memory:" php bin/console cache:warmup \
    && php bin/console importmap:install \
    && php bin/console asset-map:compile \
    && php bin/console assets:install --no-interaction

# Development layer
FROM ghcr.io/symfony-cli/symfony-cli:v5 AS symfony-cli
FROM composer:2 AS composer-cli
FROM base AS dev
ENV APP_ENV=dev \
    APP_DEBUG=1

## Install Symfony CLI and Composer
COPY --link --from=symfony-cli /usr/local/bin/symfony /usr/local/bin/symfony
COPY --link --from=composer-cli /usr/bin/composer /usr/bin/composer

ENTRYPOINT ["docker-entrypoint-dev.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
