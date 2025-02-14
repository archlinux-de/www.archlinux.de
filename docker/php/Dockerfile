FROM php:8.4.4-fpm-alpine

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN apk add --no-cache libarchive git
RUN ln -s /usr/lib/libarchive.so.*.*.* /usr/lib/libarchive.so
RUN apk add --no-cache pacman
RUN ln -s /usr/lib/libalpm.so.*.*.* /usr/lib/libalpm.so

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions opcache ffi apcu intl pdo_mysql
RUN IPE_DONT_ENABLE=1 install-php-extensions xdebug-stable

COPY --from=composer /usr/bin/composer /usr/bin/composer

ADD https://github.com/maxmind/MaxMind-DB/raw/main/test-data/GeoIP2-Country-Test.mmdb /usr/share/GeoIP/GeoLite2-Country.mmdb
RUN chmod 644 /usr/share/GeoIP/GeoLite2-Country.mmdb
