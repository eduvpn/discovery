#!/bin/sh

# CDN
SERVER_LIST="tromso-cdn.eduroam.no ifi2-cdn.eduroam.no"
for SERVER in ${SERVER_LIST}; do
    echo "${SERVER}..."
	rsync -e ssh -rtO --delete out/ "${SERVER}:/srv/disco.eduvpn.org/www/v2/" || echo "FAIL ${SERVER}"
done
