FROM archlinux/base:latest@sha256:5914c712b56fb4c969fccc5a6e45bd5d436490b9f21d6cfb0e79dbf77bdd5757

RUN pacman -Syu --noconfirm --needed php php-intl php-sqlite php-apcu composer xdebug geoip2-database yarn python2 base-devel mariadb-clients git
ADD etc/ /etc
