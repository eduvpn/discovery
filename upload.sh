#!/bin/sh
servers="tromso-cdn.eduroam.no ifi2-cdn.eduroam.no"
for server in $servers
do
	rsync -e 'ssh -o PasswordAuthentication=no' -rtO --delete out/ ${server}:/srv/disco.eduvpn.org/www/v2/ || echo "FAIL $server"
done
