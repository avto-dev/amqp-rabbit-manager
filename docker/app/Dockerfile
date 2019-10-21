FROM composer:1.8.5 AS composer

FROM php:7.2.23-alpine

ENV \
    OPENSSL_VERSION=1.1.0g \
    OPENSSL_SHA256="de4d501267da39310905cb6dc8c6121f7a2cad45a7707f76df828fe1b85073af" \
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
    gcc \
    coreutils \
    linux-headers

ENV PERMANENT_DEPS \
    # fix xdebug error "/usr/local/bin/docker-php-ext-enable: line 83: nm: not found"
    binutils \
    git

RUN set -xe \
    && apk add --no-cache ${PERMANENT_DEPS} \
    && apk add --no-cache --virtual .build-deps ${RUNTIME_DEPS} \
    # workaround for rabbitmq linking issue
    && ln -s /usr/lib /usr/local/lib64 \
    # Build openssl
    && mkdir -p /usr/local/src && cd $_ \
      && wget "https://www.openssl.org/source/openssl-${OPENSSL_VERSION}.tar.gz" -O "openssl-${OPENSSL_VERSION}.tar.gz" \
      && echo "$OPENSSL_SHA256" "openssl-${OPENSSL_VERSION}.tar.gz" | sha256sum -c - \
      && tar -zxvf "openssl-${OPENSSL_VERSION}.tar.gz" \
      && cd "openssl-${OPENSSL_VERSION}" \
      && ./config no-async shared --prefix=/usr/local/ssl --openssldir=/usr/local/ssl -Wl,-rpath,/usr/local/ssl/lib \
      && make && make install \
      && cp /usr/local/ssl/bin/openssl /usr/bin/openssl \
      && rm -rf "/usr/local/src/openssl-${OPENSSL_VERSION}.tar.gz" "/usr/local/src/openssl-${OPENSSL_VERSION}"

    # this c-library is required for 'php-amqp'
RUN ( git clone --branch v${RABBITMQ_VERSION} https://github.com/alanxz/rabbitmq-c.git /tmp/rabbitmq \
        && cd /tmp/rabbitmq \
        && mkdir build && cd build \
        && cmake -DCMAKE_C_FLAGS='-I/usr/local/ssl/include -L/usr/local/ssl/lib' \
        -DOPENSSL_ROOT_DIR=/usr/local/ssl  -DOPENSSL_INCLUDE_DIR=/usr/local/ssl/include -DOPENSSL_LIBRARIES=/usr/lib .. \
        && cmake --build . --target install ) \
        && rm -Rf /tmp/rabbitmq \
    && ( git clone --branch v${PHP_AMQP_VERSION} https://github.com/pdezwart/php-amqp.git /tmp/php-amqp \
        && cd /tmp/php-amqp \
        && phpize --clean \
        && phpize \
        && ./configure \
        && make \
        && make install \
        && echo 'extension=amqp.so' > /usr/local/etc/php/conf.d/amqp.ini ) \
        && rm -Rf /tmp/php-amqp \
    # install xdebug (for testing with code coverage), but not enable it
    && pecl install xdebug-2.7.2 \
    && apk del .build-deps \
    && mkdir /src ${COMPOSER_HOME} \
    && composer global require 'hirak/prestissimo' --no-interaction --no-suggest --prefer-dist \
    && ln -s /usr/bin/composer /usr/bin/c \
    && chmod -R 777 ${COMPOSER_HOME} \
    && composer --version \
    && openssl version \
    && php -v \
    && php -m

WORKDIR /src

VOLUME ["/src"]
