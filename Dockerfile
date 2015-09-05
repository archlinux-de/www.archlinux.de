FROM php:5.6

RUN apt-get update && apt-get install -yq bsdtar mariadb-client
RUN docker-php-ext-install gettext pdo_mysql mbstring
RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini
