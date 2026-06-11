# syntax=docker/dockerfile:1.7

###############################################################################
# Stage 1 — Composer dependencies
###############################################################################
FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock symfony.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

###############################################################################
# Stage 2 — Tailwind CSS binary + asset compilation
###############################################################################
FROM php:8.2-cli-alpine AS assets

WORKDIR /app

RUN apk add --no-cache curl

# Copy only what's needed to run asset-mapper + tailwind
COPY --from=vendor /app/vendor ./vendor
COPY composer.json composer.lock symfony.lock importmap.php ./
COPY assets ./assets
COPY config ./config
COPY templates ./templates
COPY public ./public

# Copy the Symfony console
COPY bin ./bin

# Install PHP extensions needed for asset compilation
RUN docker-php-ext-install opcache

ENV APP_ENV=prod
ENV APP_SECRET=placeholder_not_used_at_build_time

# Download standalone Tailwind binary (version pinned to match project config)
ARG TAILWIND_VERSION=v4.1.11
RUN curl -sL "https://github.com/tailwindlabs/tailwindcss/releases/download/${TAILWIND_VERSION}/tailwindcss-linux-x64" \
    -o /usr/local/bin/tailwindcss && chmod +x /usr/local/bin/tailwindcss

# Compile CSS and dump assets
RUN php bin/console tailwind:build --minify --env=prod && \
    php bin/console asset-map:compile --env=prod

###############################################################################
# Stage 3 — Production PHP-FPM image
###############################################################################
FROM php:8.2-fpm-alpine AS app

LABEL maintainer="amineBoukhari"

# System deps
RUN apk add --no-cache \
    acl \
    fcgi \
    libpq \
    postgresql-dev \
    icu-libs \
    icu-dev \
    libzip-dev \
    zip \
    unzip

# PHP extensions
RUN docker-php-ext-configure intl && \
    docker-php-ext-install \
        pdo_pgsql \
        pgsql \
        intl \
        opcache \
        zip

COPY docker/php/php.ini     $PHP_INI_DIR/conf.d/app.ini
COPY docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf

WORKDIR /srv/app

# Copy application source
COPY --chown=www-data:www-data . .

# Copy compiled vendor and assets from previous stages
COPY --from=vendor  --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets  --chown=www-data:www-data /app/public/assets ./public/assets

# Symfony warm-up
RUN APP_ENV=prod APP_SECRET=placeholder php bin/console cache:warmup --env=prod --no-debug 2>/dev/null || true

# Runtime env defaults (override via docker-compose or env files)
ENV APP_ENV=prod \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

EXPOSE 9000

ENTRYPOINT ["docker/php/entrypoint.sh"]
CMD ["php-fpm"]
