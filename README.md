# www.archlinux.de
This project contains the code powering [https://www.archlinux.de/](https://www.archlinux.de/),
the German Arch Linux community website.

# Dependencies
- [just](https://github.com/casey/just)
- [go](https://go.dev/)
- [pnpm](https://pnpm.io/)

## Optional
- [air](https://github.com/air-verse/air)

# Setup
1. Run `just init` to install dependencies, build and generate fixtures
2. Run `just run` to start the application locally
3. Run `just` for a full list of available commands

# Contributing
## Dev Mode
Run `just dev` to watch for Go, template and frontend changes and rebuild automatically (requires [air](https://github.com/air-verse/air)).

## Tests
For contributing you'll probably want to test your changes at least once
before submitting a pull request. Run `just test` to run all tests.
