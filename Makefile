.EXPORT_ALL_VARIABLES:
.PHONY: all init start stop restart clean rebuild update-data shell test db-test ci-test deploy install coverage rebuild-database update

UID!=id -u
GID!=id -g
COMPOSE=UID=${UID} GID=${GID} docker-compose -f docker/docker-compose.yml
COMPOSE-RUN=${COMPOSE} run --rm -u ${UID}:${GID}
PHP-RUN=${COMPOSE-RUN} php
PHP-NO-DB-RUN=${COMPOSE-RUN} --no-deps php
COMPOSER=composer --no-interaction
NODE-RUN=${COMPOSE-RUN} --no-deps encore
MARIADB-RUN=${COMPOSE-RUN} mariadb

all: init

init: start
	${PHP-RUN} bin/console cache:warmup
	${PHP-RUN} bin/console doctrine:database:create
	${PHP-RUN} bin/console doctrine:schema:create
	${PHP-RUN} bin/console app:config:update-countries
	${MAKE} update-data

update-data:
	${PHP-RUN} bin/console app:update:mirrors
	${PHP-RUN} bin/console app:update:news
	${PHP-RUN} bin/console app:update:releases
	${PHP-RUN} bin/console app:update:repositories
	${PHP-RUN} bin/console app:update:packages

start: install
	${COMPOSE} up -d
	${MARIADB-RUN} mysqladmin -uroot --wait=10 ping

stop:
	${COMPOSE} stop

restart:
	${MAKE} stop
	${MAKE} start

clean:
	${COMPOSE} down -v
	git clean -fdqx -e .idea

rebuild: clean
	${COMPOSE} build --no-cache --pull
	${MAKE}

install:
	${PHP-NO-DB-RUN} ${COMPOSER} install
	${NODE-RUN} yarn install

shell:
	${PHP-RUN} bash

test:
	${PHP-NO-DB-RUN} vendor/bin/phpcs
	${NODE-RUN} node_modules/.bin/standard 'assets/js/**/*.js' '*.js'
	${NODE-RUN} node_modules/.bin/stylelint 'assets/css/**/*.scss' 'assets/css/**/*.css'
	${PHP-NO-DB-RUN} bin/console lint:yaml config
	${PHP-NO-DB-RUN} bin/console lint:twig templates
	${PHP-NO-DB-RUN} vendor/bin/phpunit

db-test:
	${PHP-RUN} vendor/bin/phpunit -c phpunit-db.xml

ci-test: start
	${MAKE} test
	${PHP-NO-DB-RUN} bin/console security:check
	${NODE-RUN} node_modules/.bin/encore dev
	${MAKE} db-test

coverage:
	${PHP-NO-DB-RUN} phpdbg -qrr -d memory_limit=-1 vendor/bin/phpunit --coverage-html var/coverage

rebuild-database:
	${PHP-RUN} bin/console cache:clear
	${PHP-RUN} bin/console doctrine:database:drop --force --if-exists
	${MAKE} init

update:
	${PHP-NO-DB-RUN} ${COMPOSER} update
	${NODE-RUN} yarn upgrade --latest

deploy:
	chmod o-x .
	composer --no-interaction install --no-dev --optimize-autoloader
	yarn install
	bin/console cache:clear --no-debug --no-warmup
	yarn run encore production
	bin/console cache:warmup
	bin/console app:config:update-countries
	chmod o+x .
