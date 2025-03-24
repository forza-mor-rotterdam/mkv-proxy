FROM composer AS build
COPY . /app
WORKDIR /app
RUN composer install

FROM dunglas/frankenphp:1-php8.3-bookworm

ENV SERVER_NAME=localhost
ENV SERVER_PORT=:80
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

RUN install-php-extensions \
    opcache \
    zip

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app
COPY --from=build /app /app
