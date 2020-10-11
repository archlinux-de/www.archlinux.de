export UID := `id -u`
export GID := `id -g`

COMPOSE := 'docker-compose -f docker/app.yml -f docker/dev.yml -p www_archlinux_de'
COMPOSE-RUN := COMPOSE + ' run --rm -u ' + UID + ':' + GID
PHP-DB-RUN := COMPOSE-RUN + ' api'
PHP-RUN := COMPOSE-RUN + ' --no-deps api'
NODE-RUN := COMPOSE-RUN + ' --no-deps -e DISABLE_OPENCOLLECTIVE=true app'
MARIADB-RUN := COMPOSE-RUN + ' --no-deps mariadb'

default:
	just --list

install:
	{{PHP-RUN}} composer --no-interaction install
	{{NODE-RUN}} yarn install --non-interactive --frozen-lockfile

test: test-db test-e2e
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

test-db:
	#!/usr/bin/env node
	console.log('Hello db!')

test-e2e:
	#!/usr/bin/env php
	<?php echo 40 + 2;

# Run PHP command
php +args:
	{{PHP-RUN}} php {{args}}

composer *args:
	{{PHP-RUN}} composer {{args}}

console *args:
	{{PHP-RUN}} bin/console {{args}}

yarn +args:
	{{NODE-RUN}} yarn {{args}}
