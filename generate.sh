#!/bin/sh
php bin/generate.php
rm out/*.gz
#gzip -9k out/server_list.json out/organization_list.json
