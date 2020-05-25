These files are used by the eduVPN applications to fascilitate VPN server 
discovery.

The JSON files are signed using 
[minisign](https://jedisct1.github.io/minisign/).

To generate a (new) key:

    $ minisign -G -p disco.pub -s disco.key

To generate the discovery files:

    $ ./generate.sh

To sign:

    $ ./sign.sh

To upload:

    $ ./upload.sh

The files are uploaded to:

    https://disco.eduvpn.org/server_list.json
    https://disco.eduvpn.org/server_list.json.minisig

And:

    https://disco.eduvpn.org/organization_list.json
    https://disco.eduvpn.org/organization_list.json.minisig
