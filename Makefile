.PHONY: all init start stop restart clean rebuild csfix

APP-RUN=docker-compose run --rm -u $$(id -u) app

all: init

init: start
	@${APP-RUN} /app/config/ImportSchema.php
	@${APP-RUN} /app/config/UpdateCountries.php
	@${APP-RUN} /app/cronjobs/UpdateMirrors.php
	@${APP-RUN} /app/cronjobs/UpdateNews.php
	@${APP-RUN} /app/cronjobs/UpdateReleases.php
	@${APP-RUN} /app/cronjobs/UpdatePackages.php
	@${APP-RUN} /app/cronjobs/UpdatePkgstats.php

start: vendor
	@docker-compose up -d
	@${APP-RUN} mysqladmin -hdb -uroot -ppw --wait=10 ping

stop:
	@docker-compose stop

restart:
	@${MAKE} stop
	@${MAKE} start

clean: stop
	@docker-compose rm -f
	@git clean -fdqx

rebuild: clean
	@docker-compose build
	@${MAKE}

composer.lock: composer.json
	@${APP-RUN} composer update nothing

vendor: composer.lock
	@mkdir -p ~/.composer/cache
	@${APP-RUN} composer install

csfix: vendor
	@${APP-RUN} vendor/bin/php-cs-fixer fix . || true
