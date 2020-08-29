FROM archlinux/base

RUN pacman -Syu --noconfirm mariadb mariadb-clients
RUN mariadb-install-db --user=mysql --basedir=/usr --datadir=/var/lib/mysql --auth-root-authentication-method=normal
