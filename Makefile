.PHONY: all init start stop restart clean rebuild composer-update update-data shell test ci-test deploy assets coverage

APP-RUN=docker-compose run --rm -u $$(id -u) app
DB-RUN=docker-compose run --rm db
COMPOSER=composer --no-interaction

all: init

init: start
	${DB-RUN} mysqladmin -uroot create archportal
	${APP-RUN} bin/console app:config:import-schema
	${APP-RUN} bin/console app:config:update-countries
	${MAKE} update-data

update-data:
	${APP-RUN} bin/console app:update:mirrors
	${APP-RUN} bin/console app:update:news
	${APP-RUN} bin/console app:update:releases
	${APP-RUN} bin/console app:update:packages
	${APP-RUN} bin/console app:update:statistics

start: vendor assets
	docker-compose up -d
	${DB-RUN} mysqladmin -uroot --wait=10 ping

stop:
	docker-compose stop

restart:
	${MAKE} stop
	${MAKE} start

clean:
	docker-compose down -v
	git clean -fdqx -e .idea

rebuild: clean
	docker-compose build --no-cache --pull
	${MAKE}

composer-update:
	${APP-RUN} ${COMPOSER} update

composer.lock: composer.json
	${APP-RUN} ${COMPOSER} update nothing

vendor: composer.lock
	${APP-RUN} ${COMPOSER} install

shell:
	${APP-RUN} bash

test:
	${APP-RUN} vendor/bin/phpcs
	${APP-RUN} node_modules/.bin/standard 'assets/js/**/*.js' '*.js'
	${APP-RUN} vendor/bin/phpunit

ci-test: init
	${MAKE} test
	${APP-RUN} vendor/bin/security-checker security:check

assets:
	${APP-RUN} yarn install
	${APP-RUN} yarn run encore dev

coverage:
	${APP-RUN} php -d zend_extension=xdebug.so vendor/bin/phpunit --coverage-html var/coverage

deploy:
	chmod o-x .
	SYMFONY_ENV=prod composer --no-interaction install --no-dev --optimize-autoloader
	yarn install
	bin/console cache:clear --env=prod --no-debug --no-warmup
	yarn run encore production
	bin/console cache:warmup --env=prod
	chmod o+x .
