#!/bin/sh
scp out/* spion.eduvpn.nl:/var/www/html/web/disco.eduvpn.org/v2

# CDN
SERVER_LIST="tromso-cdn.eduroam.no ifi2-cdn.eduroam.no"
for SERVER in ${SERVER_LIST}; do
	rsync -e ssh -rtO --delete out/ "${SERVER}:/srv/disco.eduvpn.org/www/v2/" || echo "FAIL ${SERVER}"
done
