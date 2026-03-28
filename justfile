set quiet := true

export CGO_ENABLED := '0'
export PORT := '8080'
export DATABASE := 'tmp/archded.db'

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

# generate Go code and templ templates
build-templates:
    go generate ./...
    go tool templ generate

# build the production binary
build: build-assets build-templates
    go build -tags production -o archded -ldflags="-s -w" -trimpath

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
    go get -u ./...
    go mod tidy
    go get -u -t all
    go mod tidy

# update pnpm dependencies
update-pnpm:
    pnpm update --latest

# update all dependencies to latest versions
update: update-go update-pnpm

# fetch all external data into the local database
update-data: update-packages update-news update-mirrors update-releases update-package-popularities update-mirror-popularities update-planet

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

# fetch package popularity data from pkgstats.archlinux.de
update-package-popularities:
    go run . update-package-popularities

# fetch mirror popularity data from pkgstats.archlinux.de
update-mirror-popularities:
    go run . update-mirror-popularities

# fetch planet feed data from community blogs
update-planet:
    go run . update-planet

# generate test coverage report
coverage:
    #!/usr/bin/env bash
    set -euo pipefail
    go test -coverpkg=./... -coverprofile coverage.out ./...
    go tool cover -func=coverage.out
