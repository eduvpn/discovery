These files are used by the eduVPN applications to fascilitate VPN server 
discovery.

The JSON files are signed using 
[minisign](https://jedisct1.github.io/minisign/).

To fetch the repository:

    $ git clone git@github.com:eduvpn/discovery.git
    $ cd discovery
    $ git checkout new-disco

To generate a (new) key:

    $ minisign -G -p disco.pub -s disco.key

Make sure you are on the latest commit in case someone else updated the 
discovery files in the meantime, you don't want to overwrite those!

    $ git pull

To generate the (new) discovery files:

    $ ./generate.sh

To sign:

    $ ./sign.sh

To commit:

    $ git commit -a -m 'add organization X'
    $ git push origin new-disco

To upload:

    $ ./upload.sh

The files are uploaded to:

    https://disco.eduvpn.org/server_list.json
    https://disco.eduvpn.org/server_list.json.minisig

And:

    https://disco.eduvpn.org/organization_list.json
    https://disco.eduvpn.org/organization_list.json.minisig

### Secure Internet 

| Status | `baseUrl`                       | Notes                               | Authentication URL Template                                                           | Metadata URL |
| ------ | ------------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------- | ------------ |
| ✅️ | `https://nl.eduvpn.org/`            |                                     | `https://nl.eduvpn.org/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`           | `https://metadata.surfconext.nl/sp/https%253A%252F%252Fnl.eduvpn.org%252Fsaml`, `https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php` |
| ✅️ | `https://eduvpn1.funet.fi/`         |                                     | `https://eduvpn1.funet.fi/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`  | `https://haka.funet.fi/metadata/haka-metadata.xml` |
| ✅️ | `eduvpn.renu.ac.ug`                 |                                     | `https://eduvpn.renu.ac.ug/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://rif.renu.ac.ug/rr/metadata/federation/RIF/IDP/metadata.xml` |
|    | `https://eduvpn1.eduvpn.de/`        | Mail sent (to confirm Metadata URL) | `https://eduvpn1.eduvpn.de/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`              | `https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml` |
|    | `eduvpn.rash.al`                    |                                     | | |
|    | `eduvpn.deic.dk`                    | Hub/Proxy (`samlp:Scoping`)         | _TBD_ | `https://metadata.wayf.dk/birk-idp.xml` |
|    | `eduvpn.eenet.ee`                   | Currently broken (mail sent)        | | |
|    | `eduvpn-poc.renater.fr`             | Seems to have only 1 IdP?           | | | 
|    | `eduvpn.marwan.ma`                  | Mail sent                           | | |
|    | `guest.eduvpn.no`                   | Mail sent (Feide)                   | | |
|    | `vpn.pern.edu.pk`                   |                                     | | |
|    | `eduvpn.ac.lk`                      |                                     | | |
|    | `eduvpn.uran.ua`                    | Seems to have only 1 IdP?           | | |
|    | `gdpt-eduvpndev1.tnd.aarnet.edu.au` | U/P login only                      | _N/A_ | _N/A_ |
