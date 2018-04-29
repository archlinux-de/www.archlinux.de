.PHONY: all init start stop restart clean rebuild update-data shell test ci-test deploy install coverage rebuild-database update

APP-RUN=docker-compose run --rm -u $$(id -u) app
APP-NO-DB-RUN=docker-compose run --rm -u $$(id -u) --no-deps app
DB-RUN=docker-compose run --rm db
COMPOSER=composer --no-interaction

all: init

init: start
	${APP-RUN} bin/console cache:warmup
	${APP-RUN} bin/console doctrine:database:create
	${APP-RUN} bin/console doctrine:schema:create
	${APP-RUN} bin/console app:config:update-countries
	${MAKE} update-data

update-data:
	${APP-RUN} bin/console app:update:mirrors
	${APP-RUN} bin/console app:update:news
	${APP-RUN} bin/console app:update:releases
	${APP-RUN} bin/console app:update:repositories
	${APP-RUN} bin/console app:update:packages

start: install
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

install:
	${APP-NO-DB-RUN} ${COMPOSER} install
	${APP-NO-DB-RUN} yarn install

shell:
	${APP-RUN} bash

test:
	${APP-NO-DB-RUN} vendor/bin/phpcs
	${APP-NO-DB-RUN} node_modules/.bin/standard 'assets/js/**/*.js' '*.js'
	${APP-NO-DB-RUN} node_modules/.bin/stylelint 'assets/css/**/*.scss' 'assets/css/**/*.css'
	${APP-NO-DB-RUN} vendor/bin/phpunit

ci-test: install
	${MAKE} test
	${APP-NO-DB-RUN} vendor/bin/security-checker security:check
	${APP-NO-DB-RUN} node_modules/.bin/encore dev

coverage:
	${APP-NO-DB-RUN} phpdbg -qrr -d memory_limit=-1 vendor/bin/phpunit --coverage-html var/coverage

rebuild-database:
	${APP-RUN} bin/console cache:clear
	${APP-RUN} bin/console doctrine:database:drop --force --if-exists
	${MAKE} init

update:
	${APP-NO-DB-RUN} ${COMPOSER} update
	${APP-NO-DB-RUN} yarn upgrade --latest

deploy:
	chmod o-x .
	composer --no-interaction install --no-dev --optimize-autoloader
	yarn install
	bin/console cache:clear --no-debug --no-warmup
	yarn run encore production
	bin/console cache:warmup
	bin/console app:config:update-countries
	chmod o+x .
