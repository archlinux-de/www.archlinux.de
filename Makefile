.DEFAULT: init
.PHONY: init start stop status restart clean

init: .composer-install start
	@docker-compose run --rm app /app/config/ImportSchema.php
	@docker-compose run --rm app /app/config/UpdateCountries.php
	@docker-compose run --rm app /app/cronjobs/UpdateMirrors.php
	@docker-compose run --rm app /app/cronjobs/UpdateNews.php
	@docker-compose run --rm app /app/cronjobs/UpdateReleases.php
	@docker-compose run --rm app /app/cronjobs/UpdatePackages.php
	@docker-compose run --rm app /app/cronjobs/UpdatePkgstats.php

start:
	@docker-compose up -d
	@docker-compose run --rm app mysqladmin -hdb -uroot -ppw --wait=10 ping

stop:
	@docker-compose stop

status:
	@docker-compose ps

restart: stop start

clean: stop
	@docker-compose rm -f
	@rm -rf vendor

.composer-install:
	@docker run --rm -it -v $$(pwd):/app -u $$(id -u) composer/composer install
