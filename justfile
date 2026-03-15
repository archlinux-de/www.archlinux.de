set quiet := true

export CGO_ENABLED := '0'
export PORT := '8080'
export DATABASE := 'tmp/www.db'

[private]
default:
    just --list

# first-time setup: install dependencies, build and fetch data
init: install build-assets build-templates update-data

# install dependencies
install:
    go mod download
    pnpm install

# compile frontend assets
build-assets:
    pnpm run build

# generate templ templates
build-templates:
    go tool templ generate

# build the production binary
build: build-assets build-templates
    go build -tags production -o www -ldflags="-s -w" -trimpath

# run the application locally
run:
    go run -tags development .

# open the local dev server in the default browser
open:
    xdg-open 'http://localhost:{{ PORT }}'

# watch for template and Go changes and rebuild automatically
[parallel]
dev: dev-assets dev-server

[private]
dev-assets:
    pnpm exec vite build --watch

[private]
dev-server:
    air

# run all tests
test:
    go test ./...

# run all linters
lint:
    pnpm run lint
    golangci-lint run
    just --fmt --unstable --check

# auto-format all code
fmt:
    pnpm run format
    go tool templ fmt .
    golangci-lint fmt
    just --fmt --unstable

# remove all untracked and ignored files
clean:
    git clean -fdqx -e .idea

# remove untracked files, reinstall dependencies and rebuild
rebuild: clean install build-assets build-templates

# list outdated direct dependencies
outdated:
    pnpm outdated
    go list -u -m -json all | jq -r 'select(.Update and (.Indirect | not)) | "\(.Path): \(.Version) -> \(.Update.Version)"'

# audit dependencies for known vulnerabilities
audit:
    pnpm audit --prod

# update Go toolchain and module dependencies
update-go:
    go mod edit -go=$(go env GOVERSION | sed 's/go//; s/-.*//')
    go get -u -t all
    go mod tidy

# update pnpm dependencies
update-pnpm:
    pnpm update --latest

# update all dependencies to latest versions
update: update-go update-pnpm

# fetch all external data into the local database
update-data: update-countries update-packages update-news update-mirrors update-releases update-popularities

# fetch package data from Arch Linux repositories
update-packages:
    go run . update-packages

# fetch news from archlinux.org
update-news:
    go run . update-news

# fetch mirror list from archlinux.org
update-mirrors:
    go run . update-mirrors

# fetch release data from archlinux.org
update-releases:
    go run . update-releases

# fetch country data from restcountries.com
update-countries:
    go run . update-countries

# fetch popularity data from pkgstats.archlinux.de
update-popularities:
    go run . update-popularities

# generate test coverage report
coverage:
    #!/usr/bin/env bash
    set -euo pipefail
    go test -coverpkg=./... -coverprofile coverage.out ./...
    go tool cover -func=coverage.out
