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

The web server adds the `Cache-Control: no-cache` header to make sure that 
HTTP clients will cache, but always verify that they have the latest version 
of the JSON and minisig files before using them:

    <Directory "/var/www/html/web/disco.eduvpn.org">
        <Files "*.json*">
            Header set Cache-Control "no-cache"
        </Files>
    </Directory>

### Secure Internet 

| Status | Server                              | Notes                               | Authentication URL Template                                                           | Metadata URL |
| ------ | ----------------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------- | ------------ |
| ✅️     | `nl.eduvpn.org`                     |                                     | `https://nl.eduvpn.org/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`           | `https://metadata.surfconext.nl/sp/https%253A%252F%252Fnl.eduvpn.org%252Fsaml`, `https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php` |
| ✅️     | `eduvpn1.funet.fi`                  |                                     | `https://eduvpn1.funet.fi/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`  | `https://haka.funet.fi/metadata/haka-metadata.xml` |
| ✅️     | `eduvpn.renu.ac.ug`                 |                                     | `https://eduvpn.renu.ac.ug/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@` | `https://rif.renu.ac.ug/rr/metadata/federation/RIF/IDP/metadata.xml` |
| ✅️     | `eduvpn.marwan.ma`                  |                                     | `https://eduvpn.marwan.ma/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`               | `https://www.eduidm.ma/metadata/eduidm.xml` |
| ✅️     | `vpn.pern.edu.pk`                   |                                     | `https://vpn.pern.edu.pk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`   | `https://rr.pern.edu.pk/rr3/signedmetadata/federation/PERN-Federation/metadata.xml` |
| ✅️     | `eduvpn.ac.lk`                      |                                     | `https://eduvpn.ac.lk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@`      | `https://fr.ac.lk/signedmetadata/metadata.xml` |
|        | `eduvpn1.eduvpn.de`                 | Mail sent (to confirm Metadata URL) | `https://eduvpn1.eduvpn.de/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@`              | `https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml` |
|        | `eduvpn.deic.dk`                    | Must switch to php-saml-sp first    | `https://eduvpn.deic.dk/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=https://wayf.wayf.dk&ScopingIdpList=@ORG_ID@` | For Organization List: `https://metadata.wayf.dk/birk-idp.xml`, for SP: `https://metadata.wayf.dk/wayf-metadata.xml` |
|        | `eduvpn.eenet.ee`                   | Hub & Spoke, must switch to php-saml-sp first... | | `https://taeva.taat.edu.ee/module.php/janus/exportentities.php?state=prodaccepted&mimetype=application%2Fsamlmetadata%2Bxml&external=null` |
|        | `eduvpn.rash.al`                    | 1 IdP with multiple organizations   | | |
|        | `eduvpn-poc.renater.fr`             | Seems to have only 1 IdP?           | | | 
|        | `guest.eduvpn.no`                   | Mail sent (Feide)                   | | |
|        | `eduvpn.uran.ua`                    | Seems to have only 1 IdP?           | | |
|        | `gdpt-eduvpndev1.tnd.aarnet.edu.au` | U/P login only                      | _N/A_ | _N/A_ |

# Open Issues

With SAML proxies we somehow need to indicate which IdP is to be used. This can
typically be done using `AuthnRequest` "scoping". The SP needs to support this
through a query parameter.

Support for this will be part of the next release of 
[php-saml-sp](https://github.com/fkooman/php-saml-sp).

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
