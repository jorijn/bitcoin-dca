FROM composer:latest AS vendor

WORKDIR /app/

COPY composer.json composer.lock /app/

RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist \
    --no-autoloader \
    --no-dev

COPY . /app/

RUN composer dump-autoload \
    --no-scripts \
    --optimize \
    --no-dev \
    --no-interaction \
    --no-plugins \
    --classmap-authoritative

FROM php:7.4-alpine-cli

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY . /app/
COPY --from=vendor /app/vendor/ /app/vendor/

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint

ENTRYPOINT ["docker-entrypoint"]
