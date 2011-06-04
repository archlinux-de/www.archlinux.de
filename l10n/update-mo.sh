#!/bin/bash

for po in $(find . -type f -name '*.po'); do
	mo="${po/.po/.mo}"
	echo -ne "creating ${mo}\n\t"
	msgfmt -c --statistics -o "${mo}" "${po}"
done
