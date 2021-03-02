#!/bin/sh

# verify JSON file
if ! python -mjson.tool server_list.json > /dev/null;
then
	echo "ERROR in server_list.json!"
	exit 1
fi

php bin/generate.php
rm -f out/*.gz
#gzip -9k out/server_list.json out/organization_list.json
