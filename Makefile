.PHONY: init start stop clean container

APP-RUN=docker-compose run --rm -u $$(id -u) app

all: vendor

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

clean: stop
	@docker-compose rm -f
	@git clean -fdqx

container: clean
	@docker-compose build

composer.lock: composer.json
	@${APP-RUN} composer update -o nothing

vendor: composer.lock
	@${APP-RUN} composer install -o
