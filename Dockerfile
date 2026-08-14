FROM dunglas/frankenphp:1-php8.4-bookworm AS base
WORKDIR /app

RUN install-php-extensions \
        pdo_mysql \
        intl \
        zip \
        sockets \
        calendar \
        mbstring \
        exif \
        gd \
        xsl \
        mysqli \
        opcache


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
        --ignore-platform-reqs

COPY . .
RUN APP_ENV=prod composer dump-autoload --classmap-authoritative --no-dev


FROM base AS prod
ENV APP_ENV=prod \
    APP_DEBUG=0

ARG USER=appuser

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zzz-app.ini
COPY --from=build /build /app

RUN DATABASE_URL="sqlite:///:memory:" php bin/console cache:clear --no-warmup \
    && DATABASE_URL="sqlite:///:memory:" php bin/console cache:warmup \
    && useradd ${USER} \
    && setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R ${USER}:${USER} /app /config/caddy /data/caddy

USER ${USER}


FROM ghcr.io/symfony-cli/symfony-cli:v5 AS symfony-cli

FROM base AS dev
ENV APP_ENV=dev \
    APP_DEBUG=1

# Install Symfony CLI and Composer
COPY --link --from=symfony-cli /usr/local/bin/symfony /usr/local/bin/symfony
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY --chmod=755 docker/php/docker-entrypoint-dev.sh /usr/local/bin/docker-entrypoint-dev.sh
ENTRYPOINT ["docker-entrypoint-dev.sh"]

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
