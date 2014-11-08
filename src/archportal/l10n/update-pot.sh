#!/bin/bash

find ../ -type f -name '*.php' \
	| xgettext \
		-k \
		-kgetText \
		-kngetText:1,2 \
		--from-code utf-8 \
		-d archportal \
		-o archportal.pot \
		-L PHP \
		--no-wrap \
		-F \
		--copyright-holder='Pierre Schmitz' \
		--package-name='archportal' \
		--msgid-bugs-address='pierre@archlinux.de' \
		--package-version='' \
		-f -
