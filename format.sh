#!/bin/sh

for i in *.json
do
	php sort.php "${i}" > tmp
	mv tmp "${i}"
	cat "${i}" | python -mjson.tool > tmp
        mv tmp "${i}"
done
