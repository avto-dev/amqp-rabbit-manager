FROM composer:1.8.5 AS composer

# for php 7.2.0 installed openssl-1.0 (instead openssl-1.1) and does not work with rabbitmq-c
FROM php:7.2.23-alpine

ENV \
    # <https://github.com/alanxz/rabbitmq-c>
    RABBITMQ_VERSION="0.9.0" \
    # ext-amqp <https://github.com/pdezwart/php-amqp>
    PHP_AMQP_VERSION="1.9.4" \
    COMPOSER_ALLOW_SUPERUSER="1" \
    COMPOSER_HOME="/tmp/composer"

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV RUNTIME_DEPS \
    openssl-dev \
    autoconf \
    pkgconf \
    cmake \
    unzip \
    curl \
    make \
    file \
    re2c \
    g++ \
    gcc

ENV PERMANENT_DEPS \
    # fix xdebug error "/usr/local/bin/docker-php-ext-enable: line 83: nm: not found"
    binutils \
    git

RUN set -xe \
    && apk add --no-cache ${PERMANENT_DEPS} \
    && apk add --no-cache --virtual .build-deps ${RUNTIME_DEPS} \
    # workaround for rabbitmq linking issue
    && ln -s /usr/lib /usr/local/lib64 \
    # Install php-ext-amqp
    && apk add --no-cache --no-progress --virtual BUILD_DEPS_PHP_AMQP rabbitmq-c-dev \
    && apk add --no-cache --no-progress rabbitmq-c \
    && pecl install amqp \
    && docker-php-ext-enable amqp \
    # install xdebug (for testing with code coverage), but not enable it
    && pecl install xdebug-2.7.2 \
    && apk del .build-deps \
    && mkdir /src ${COMPOSER_HOME} \
    && composer global require 'hirak/prestissimo' --no-interaction --no-suggest --prefer-dist \
    && ln -s /usr/bin/composer /usr/bin/c \
    && chmod -R 777 ${COMPOSER_HOME} \
    && composer --version \
    && php -v \
    && php -m

WORKDIR /src

VOLUME ["/src"]
