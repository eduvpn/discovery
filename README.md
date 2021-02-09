# Server Discovery for eduVPN

These files are used by the eduVPN applications to facilitate VPN server 
discovery on the `disco.eduvpn.org` domain.

The JSON files are signed using 
[minisign](https://jedisct1.github.io/minisign/).

To fetch the repository:

    $ git clone git@git.sr.ht:~eduvpn/discovery
    $ cd discovery

To generate a (new) key:

    $ minisign -G -p disco.pub -s disco.key

Make sure you are on the latest commit in case someone else updated the 
discovery files in the meantime, you don't want to overwrite those!

    $ git pull

Modify `server_list.json` to add/remove servers. Look at the other entries on
how to do this exactly. More documentation will be added in the near future.

The `organization_list.json` file is generated from the SAML metadata fetched 
from the URLs mentioned in the `server_list.json`. These files are cached in 
the `cache/` directory. You probably need to delete those regularly in order 
to sync up with any added/removed IdPs.

    $ ./generate.sh

To sign:

    $ ./sign.sh

To commit:

    $ git commit -a -m 'add organization X'
    $ git push origin new-disco

To upload:

    $ ./upload.sh

The files are uploaded to:

    https://disco.eduvpn.org/v2/server_list.json
    https://disco.eduvpn.org/v2/server_list.json.minisig

And:

    https://disco.eduvpn.org/v2/organization_list.json
    https://disco.eduvpn.org/v2/organization_list.json.minisig

The web server adds the `Cache-Control: no-cache` header to make sure that 
HTTP clients will cache, but always verify that they have the latest version 
of the JSON and minisig files before using them:

    <Directory "/var/www/html/web/disco.eduvpn.org">
        Header set Cache-Control "no-cache"
    </Directory>

# Public Keys

The following public keys MUST be trusted:

| Owner                | Public Key                                                 |
| -------------------- | ---------------------------------------------------------- |
| `fkooman@deic.dk`    | `RWRtBSX1alxyGX+Xn3LuZnWUT0w//B6EmTJvgaAxBMYzlQeI+jdrO6KF` |
| `jornane@uninett.no` | `RWQ68Y5/b8DED0TJ41B1LE7yAvkmavZWjDwCBUuC+Z2pP9HaSawzpEDA` |
| RoSp                 | `RWQKqtqvd0R7rUDp0rWzbtYPA3towPWcLDCl7eY9pBMMI/ohCmrS0WiM` |

# Secure Internet 

| Status | Server                              | Notes                               | Authentication URL Template                                                           | Metadata URL |
| ------ | ----------------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------- | ------------ |
| ✅️     | `nl.eduvpn.org`                     |                                     | `https://nl.eduvpn.org/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`           | `https://metadata.surfconext.nl/sp/https%253A%252F%252Fnl.eduvpn.org%252Fsaml`, `https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php` |
| ✅️     | `eduvpn1.funet.fi`                  |                                     | `https://eduvpn1.funet.fi/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`  | `https://haka.funet.fi/metadata/haka-metadata.xml` |
| ✅️     | `eduvpn.renu.ac.ug`                 |                                     | `https://eduvpn.renu.ac.ug/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://rif.renu.ac.ug/rr/metadata/federation/RIF/IDP/metadata.xml` |
| ✅️     | `eduvpn.marwan.ma`                  |                                     | `https://eduvpn.marwan.ma/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`               | `https://www.eduidm.ma/metadata/eduidm.xml` |
| ✅️     | `vpn.pern.edu.pk`                   |                                     | `https://vpn.pern.edu.pk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`   | `https://rr.pern.edu.pk/rr3/signedmetadata/federation/PERN-Federation/metadata.xml` |
| ✅️     | `eduvpn.ac.lk`                      |                                     | `https://eduvpn.ac.lk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`      | `https://fr.ac.lk/signedmetadata/metadata.xml` |
| ✅️     | `eduvpn-poc.renater.fr`             |                                 | `https://eduvpn-poc.renater.fr/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://metadata.federation.renater.fr/eduVPN-58b9d/preview/preview-renater-eduVPN-metadata.xml` | 
| ✅️     | `eduvpn1.eduvpn.de`                 |                                     | `https://eduvpn1.eduvpn.de/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`              | `https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml` |
| ✅️     | `eduvpn.myren.net.my`               |                                     | `https://eduvpn.myren.net.my/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://sifulan.my/metadata/metadata.xml` |
| ✅️     | `guest.eduvpn.ac.za`                |                                     | `https://guest.eduvpn.ac.za/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://metadata.safire.ac.za/safire-idp-proxy-metadata.xml` |
|        | `eduvpn.deic.dk`                    | Must switch to php-saml-sp first    | `https://eduvpn.deic.dk/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=https://wayf.wayf.dk&ScopingIdpList=@ORG_ID@` | For Organization List: `https://metadata.wayf.dk/birk-idp.xml`, for SP: `https://metadata.wayf.dk/wayf-metadata.xml` |
|        | `eduvpn.eenet.ee`                   | Hub & Spoke, must switch to php-saml-sp first... | | `https://taeva.taat.edu.ee/module.php/janus/exportentities.php?state=prodaccepted&mimetype=application%2Fsamlmetadata%2Bxml&external=null` |
|        | `eduvpn.rash.al`                    | 1 IdP with multiple organizations   | | |
|        | `guest.eduvpn.no`                   | Mail sent (Feide)                   | | |
|        | `eduvpn.uran.ua`                    | Seems to have only 1 IdP?           | | |
|        | `gdpt-eduvpndev1.tnd.aarnet.edu.au` | U/P login only                      | _N/A_ | _N/A_ |

# Open Issues

With SAML proxies we somehow need to indicate which IdP is to be used. This can
typically be done using `AuthnRequest` "scoping". The SP needs to support this
through a query parameter.

Support for this will be part of the next release of 
[php-saml-sp](https://sr.ht/~fkooman/php-saml-sp).

With Feide we need to be even more clever as `AuthnRequest` "scoping" may not 
be supported (unconfirmed as of 2020-05-26). There we may not have any other 
choice than be clever `ReturnTo` (double) encoding.

# Triggering SAML Login through URL

## Mellon

[Documentation](https://github.com/latchset/mod_auth_mellon#manual-login)

- `ReturnTo`
- `IdP`

URL format: `/saml/login?ReturnTo=X&IdP=Y`

## Shibboleth

[Documentation](https://wiki.shibboleth.net/confluence/display/SP3/SessionInitiator#SessionInitiator-InitiatorProtocol)

- `target`
- `entityID`

URL format: `https://sp.example.org/Shibboleth.sso/Login?target=https%3A%2F%2Fsp.example.org%2Fresource.asp&entityID=https%3A%2F%2Fidp.example.org%2Fidp%2Fshibboleth`

## simpleSAMLphp

See [this](https://github.com/simplesamlphp/simplesamlphp/blob/master/modules/core/www/as_login.php). Seems `saml:idp` is not documented...

- `ReturnTo`
- `AuthId`
- `saml:idp`

URL format: `/simplesaml/module.php/core/as_login.php?AuthId=<authentication source>&ReturnTo=<return URL>`

## php-saml-sp

- `ReturnTo`
- `IdP`
- `ScopingIdPList` (for `<samlp:Scoping>`)

URL format: `/php-saml-sp/login?ReturnTo=X&IdP=Y`
