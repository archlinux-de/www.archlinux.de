FROM php:8.3-fpm-alpine

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN apk add --no-cache libarchive git
RUN apk add --no-cache -X http://dl-cdn.alpinelinux.org/alpine/edge/community pacman

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions opcache ffi apcu intl pdo_mysql
RUN IPE_DONT_ENABLE=1 install-php-extensions xdebug-stable

COPY --from=composer /usr/bin/composer /usr/bin/composer

ADD https://github.com/maxmind/MaxMind-DB/raw/main/test-data/GeoIP2-Country-Test.mmdb /usr/share/GeoIP/GeoLite2-Country.mmdb
RUN chmod 644 /usr/share/GeoIP/GeoLite2-Country.mmdb
