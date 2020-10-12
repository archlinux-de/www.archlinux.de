export UID := `id -u`
export GID := `id -g`

COMPOSE := 'docker-compose -f docker/app.yml '+`[ "${CI-}" != "true" ] && echo '-f docker/dev.yml' || echo ''`+' -p www_archlinux_de'
COMPOSE-RUN := COMPOSE + ' run --rm -u ' + UID + ':' + GID
PHP-DB-RUN := COMPOSE-RUN + ' api'
PHP-RUN := COMPOSE-RUN + ' --no-deps api'
NODE-RUN := COMPOSE-RUN + ' --no-deps -e DISABLE_OPENCOLLECTIVE=true app'
MARIADB-RUN := COMPOSE-RUN + ' --no-deps mariadb'

default:
	just --list

init: start
	{{PHP-DB-RUN}} bin/console cache:warmup
	{{PHP-DB-RUN}} bin/console doctrine:database:create
	{{PHP-DB-RUN}} bin/console doctrine:schema:create
	{{PHP-DB-RUN}} bin/console doctrine:migrations:sync-metadata-storage --no-interaction
	{{PHP-DB-RUN}} bin/console doctrine:migrations:version --add --all --no-interaction
	{{PHP-DB-RUN}} bin/console app:index:mirrors
	{{PHP-DB-RUN}} bin/console app:index:news
	{{PHP-DB-RUN}} bin/console app:index:packages
	{{PHP-DB-RUN}} bin/console app:index:releases
	{{PHP-DB-RUN}} bin/console app:config:update-countries
	{{PHP-DB-RUN}} bin/console app:update:mirrors
	{{PHP-DB-RUN}} bin/console app:update:news
	{{PHP-DB-RUN}} bin/console app:update:releases
	{{PHP-DB-RUN}} bin/console app:update:repositories
	{{PHP-DB-RUN}} bin/console app:update:packages

start:
	{{COMPOSE}} up -d
	{{MARIADB-RUN}} mysqladmin -uroot -hmariadb --wait=10 ping
	{{COMPOSE-RUN}} wait -c elasticsearch:9200 -t 60
	{{COMPOSE-RUN}} wait -c elasticsearch-test:9200 -t 60

start-db:
	{{COMPOSE}} up -d mariadb elasticsearch-test
	{{MARIADB-RUN}} mysqladmin -uroot -hmariadb --wait=10 ping
	{{COMPOSE-RUN}} wait -c elasticsearch-test:9200 -t 60

stop:
	{{COMPOSE}} stop

clean:
	{{COMPOSE}} down -v
	git clean -fdqx -e .idea

rebuild: clean
	{{COMPOSE}} build --pull
	just install
	just init
	just stop

install:
	{{PHP-RUN}} composer --no-interaction install
	{{NODE-RUN}} yarn install --non-interactive --frozen-lockfile

php +args:
	{{PHP-RUN}} php {{args}}

composer *args:
	{{PHP-RUN}} composer {{args}}

console *args:
	{{PHP-RUN}} bin/console {{args}}

node +args:
	{{NODE-RUN}} node {{args}}

yarn +args:
	{{NODE-RUN}} yarn {{args}}

test:
	{{PHP-RUN}} composer validate
	{{PHP-RUN}} vendor/bin/phpcs
	{{NODE-RUN}} node_modules/.bin/eslint src --ext js --ext vue
	{{NODE-RUN}} node_modules/.bin/stylelint 'src/assets/css/**/*.scss' 'src/assets/css/**/*.css' 'src/**/*.vue'
	{{NODE-RUN}} node_modules/.bin/jest
	{{PHP-RUN}} bin/console lint:yaml config
	{{PHP-RUN}} bin/console lint:twig templates
	{{NODE-RUN}} yarn build --modern --dest $(mktemp -d)
	{{PHP-RUN}} php -dmemory_limit=-1 vendor/bin/phpstan analyse
	{{PHP-RUN}} vendor/bin/phpunit

test-e2e:
	#!/usr/bin/env sh
	if [ "${CI-}" = "true" ]; then
		just init
		echo Running as user crashes Cypress on CI
		{{COMPOSE}} -f docker/cypress-run.yml run --rm --no-deps cypress run --project tests/e2e --browser chrome --headless
	else
		{{COMPOSE}} -f docker/cypress-run.yml run --rm -u ${UID}:${GID} --no-deps cypress run --project tests/e2e --browser chrome --headless
	fi

cypress-open:
	{{COMPOSE}} -f docker/cypress-open.yml run -d --rm -u ${UID}:${GID} --no-deps cypress open --project tests/e2e

test-db: start-db
	{{PHP-DB-RUN}} vendor/bin/phpunit -c phpunit-db.xml

test-db-migrations: start-db
	{{PHP-DB-RUN}} vendor/bin/phpunit -c phpunit-db.xml --testsuite 'Doctrine Migrations Test'

update-elasticsearch-fixtures: start-db
	rm -f api/tests/ElasticsearchFixtures/*.json
	{{COMPOSE-RUN}} -e ELASTICSEARCH_URL=http://elasticsearch-test:9200 -e ELASTICSEARCH_MOCK_MODE=write api vendor/bin/phpunit

test-coverage:
	{{NODE-RUN}} node_modules/.bin/jest --coverage --coverageDirectory var/coverage/jest
	{{PHP-RUN}} phpdbg -qrr -d memory_limit=-1 vendor/bin/phpunit --coverage-html var/coverage/phpunit

test-db-coverage: start-db
	{{PHP-RUN}} phpdbg -qrr -d memory_limit=-1 vendor/bin/phpunit --coverage-html var/coverage -c phpunit-db.xml

test-security:
	{{PHP-RUN}} bin/console security:check
	{{NODE-RUN}} yarn audit --groups dependencies

fix-code-style:
	{{PHP-RUN}} vendor/bin/phpcbf || true
	{{NODE-RUN}} node_modules/.bin/eslint src --fix --ext js --ext vue
	{{NODE-RUN}} node_modules/.bin/stylelint --fix 'src/assets/css/**/*.scss' 'src/assets/css/**/*.css' 'src/**/*.vue'

_update-cypress-image:
	#!/usr/bin/env node
	const https = require('https')
	const fs = require('fs')
	https.get('https://hub.docker.com/v2/repositories/cypress/included/tags/?page_size=1', response => {
		let data = ''
		response.on('data', (chunk) => { data += chunk })
		response.on('end', () => {
			['docker/cypress-open.yml', 'docker/cypress-run.yml'].forEach(file => {
				const newFile = fs
					.readFileSync(file, 'utf8')
					.replace(/cypress\/included:.+/, 'cypress/included:' + JSON.parse(data).results[0].name)
				fs.writeFileSync(file, newFile)
			})
		})
	})

update:
	{{PHP-RUN}} composer --no-interaction update
	{{PHP-RUN}} composer --no-interaction update --lock --no-scripts
	{{NODE-RUN}} yarn upgrade --non-interactive --latest
	just _update-cypress-image

deploy:
	cd app && yarn install --non-interactive --frozen-lockfile
	cd app && yarn build --modern --no-clean
	cd app && find dist -type f -mtime +30 -delete
	cd app && find dist -type d -empty -delete
	cd api && composer --no-interaction install --prefer-dist --no-dev --optimize-autoloader
	cd api && composer dump-env prod
	systemctl restart php-fpm@www.service
	cd api && bin/console doctrine:migrations:sync-metadata-storage --no-interaction
	cd api && bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
	cd api && bin/console app:config:update-countries
	cd api && bin/console app:update:repositories

deploy-permissions:
	cd api && sudo setfacl -dR -m u:php-www:rwX -m u:deployer:rwX var
	cd api && sudo setfacl -R -m u:php-www:rwX -m u:deployer:rwX var
