FROM archlinux/base:latest@sha256:5914c712b56fb4c969fccc5a6e45bd5d436490b9f21d6cfb0e79dbf77bdd5757

RUN pacman -Syu --noconfirm mariadb
ADD etc/ /etc
RUN mysql_install_db --user=mysql --basedir=/usr --datadir=/var/lib/mysql
