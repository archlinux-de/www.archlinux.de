FROM php:5.6

RUN apt-get update
RUN apt-get install -yq bsdtar mariadb-client locales zlib1g-dev
RUN docker-php-ext-install gettext pdo_mysql mbstring zip

RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini
RUN echo "de_DE.UTF-8 UTF-8" >> /etc/locale.gen
RUN echo "en_US.UTF-8 UTF-8" >> /etc/locale.gen
RUN locale-gen
RUN update-locale LANG=en_US.UTF-8

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

VOLUME ["/app"]
WORKDIR /app
