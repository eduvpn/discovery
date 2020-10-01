<?php

require_once \dirname(__DIR__).'/src/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server
@\mkdir(\dirname(__DIR__).'/cache', 0711, true);
@\mkdir(\dirname(__DIR__).'/out', 0711, true);

$unixTime = time();

$keywordMapping = [
    'https://hku.eduvpn.nl/' => [
        'en' => 'hku',
    ]
];

$metadataMapping = [
    'https://nl.eduvpn.org/' => ['https://metadata.surfconext.nl/sp/https%253A%252F%252Fnl.eduvpn.org%252Fsaml', 'https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php'],
    'https://eduvpn1.eduvpn.de/' => ['https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml'],
    'https://eduvpn.deic.dk/' => ['https://metadata.wayf.dk/birk-idp.xml'],
    // https://wiki.eduuni.fi/display/CSCHAKA/Haka+metadata
    'https://eduvpn1.funet.fi/' => ['https://haka.funet.fi/metadata/haka-metadata.xml'],
    'https://eduvpn.renu.ac.ug/' => ['https://rif.renu.ac.ug/rr/metadata/federation/RIF/IDP/metadata.xml'],
    'https://eduvpn.marwan.ma/' => ['https://www.eduidm.ma/metadata/eduidm.xml'],
    'https://vpn.pern.edu.pk/' => ['https://rr.pern.edu.pk/rr3/signedmetadata/federation/PERN-Federation/metadata.xml'],
    'https://eduvpn.ac.lk/' => ['https://fr.ac.lk/signedmetadata/metadata.xml'],
    'https://eduvpn.eenet.ee/' => ['https://taeva.taat.edu.ee/module.php/janus/exportentities.php?state=prodaccepted&mimetype=application%2Fsamlmetadata%2Bxml&external=null'],
    'https://eduvpn-poc.renater.fr/' => ['https://metadata.federation.renater.fr/eduVPN-58b9d/preview/preview-renater-eduVPN-metadata.xml'],
];

$authTemplateMapping = [
    'https://nl.eduvpn.org/' => 'https://nl.eduvpn.org/php-saml-sp/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@',
    'https://eduvpn1.eduvpn.de/' => 'https://eduvpn1.eduvpn.de/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@',
    'https://eduvpn1.funet.fi/' => 'https://eduvpn1.funet.fi/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@',
    'https://eduvpn.renu.ac.ug/' => 'https://eduvpn.renu.ac.ug/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@',
    'https://eduvpn.marwan.ma/' => 'https://eduvpn.marwan.ma/saml/login?ReturnTo=@RETURN_TO@&IdP=@ORG_ID@',
    'https://vpn.pern.edu.pk/' => 'https://vpn.pern.edu.pk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@',
    'https://eduvpn.ac.lk/' => 'https://eduvpn.ac.lk/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@',
    'https://eduvpn-poc.renater.fr/' => 'https://eduvpn-poc.renater.fr/Shibboleth.sso/Login?entityID=@ORG_ID@&target=@RETURN_TO@',
];

function ccRewrite($baseUri) {
    $ccMapping = [
        'https://eduvpn.rash.al/' => 'AL',
        'https://gdpt-eduvpndev1.tnd.aarnet.edu.au/' => 'AU',
        'https://eduvpn.deic.dk/' => 'DK',
        'https://eduvpn.eenet.ee/' => 'EE',
        'https://eduvpn1.funet.fi/' => 'FI',
        'https://eduvpn-poc.renater.fr/' => 'FR',
        'https://eduvpn1.eduvpn.de/' => 'DE',
        'https://eduvpn.marwan.ma/' => 'MA',
        'https://guest.eduvpn.no/' => 'NO',
        'https://vpn.pern.edu.pk/' => 'PK',
        'https://eduvpn.ac.lk/' => 'LK',
        'https://nl.eduvpn.org/' => 'NL',
        'https://eduvpn.renu.ac.ug/' => 'UG',
        'https://eduvpn.uran.ua/' => 'UA',
    ];

    return $ccMapping[$baseUri];
}

$feideSpList = [
    'https://guest.eduvpn.no/',
];

$mappingData = getMapping(\json_decode(\file_get_contents('secure_internet.json'), true), $metadataMapping, $feideSpList);

// now retrieve the information of the IdPs through their SAML metadata URLs or
// other means...
$organizationServerList = getOrganizationServerList($mappingData);

// now remove the servers from the entries and put them in separate files
// based on the "orgId"...
writeOrganizationList($organizationServerList, $unixTime);

$serverList = [];
$serverList = \array_merge($serverList, rewriteSecureInternet($authTemplateMapping));
$serverList = \array_merge($serverList, rewriteInstituteAccess($keywordMapping));
\file_put_contents('out/server_list.json', \json_encode(['v' => $unixTime, 'server_list' => $serverList], JSON_UNESCAPED_SLASHES));

function writeOrganizationList(array $organizationServerList, $unixTime)
{
    // we only need to remove server_info_list from the entries
    foreach ($organizationServerList as $k => $v) {
    }
    \file_put_contents('out/organization_list.json', \json_encode(['v' => $unixTime, 'organization_list' => $organizationServerList], JSON_UNESCAPED_SLASHES));
}

function getOrganizationServerList(array $mappingData)
{
    $orgInfo = [];
    foreach ($mappingData as $baseUrl => $serverInfo) {
        $serverInfo['base_url'] = $baseUrl;
        if ($serverInfo['is_feide_sp']) {
            // scrape the Feide IdP WAYF
            $feideIdpList = fetchFeideIdpList($baseUrl);
            foreach ($feideIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'secure_internet_home' => $baseUrl,
                    ];
                    if (\array_key_exists('keyword_list', $idpInfo)) {
                        $orgInfo[$orgId]['keyword_list'] = $idpInfo['keyword_list'];
                    }
                }
            }
        }
        if (0 !== \count($serverInfo['metadata_url_list'])) {
            // extract the IdPs from the SAML metadata
            $samlIdpList = fetchSamlMetadataIdpList($serverInfo['metadata_url_list']);
            foreach ($samlIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'secure_internet_home' => $baseUrl,
                    ];
                    if (\array_key_exists('keyword_list', $idpInfo)) {
                        $orgInfo[$orgId]['keyword_list'] = $idpInfo['keyword_list'];
                    }
                }
            }
        }
    }

    return \array_values($orgInfo);
}

function getMapping(array $discoveryData, array $metadataMapping, array $feideSpList)
{
    $outputData = [];
    foreach ($discoveryData['instances'] as $instance) {
        $baseUri = $instance['base_uri'];
        $metadataUrlList = [];
        $isFeideSp = false;

        if (\array_key_exists($baseUri, $metadataMapping)) {
            $metadataUrlList = \array_merge($metadataUrlList, $metadataMapping[$baseUri]);
        }
        if (\in_array($baseUri, $feideSpList, true)) {
            $isFeideSp = true;
        }
        $outputData[$baseUri] = [
            'metadata_url_list' => $metadataUrlList,
            'is_feide_sp' => $isFeideSp,
        ];
    }

    return $outputData;
}

function fetchSamlMetadataIdpList(array $metadataUrlList)
{
    $idpList = [];
    foreach ($metadataUrlList as $metadataUrl) {
        try {
            $metadataLocalFile = \dirname(__DIR__).'/cache/'.\urlencode($metadataUrl);
            if (!@\file_exists($metadataLocalFile)) {
                if (false === $metadataContent = @\file_get_contents($metadataUrl)) {
                    throw new RuntimeException(\sprintf('unable to fetch "%s"', $metadataUrl));
                }
                if (false === @\file_put_contents($metadataLocalFile, $metadataContent)) {
                    throw new RuntimeException(\sprintf('unable to write "%s"', $metadataLocalFile));
                }
            }
            $md = new MetadataParserAll($metadataLocalFile);
            $idpInfoList = $md->get();
            foreach ($idpInfoList as $idpInfo) {
                $entityId = $idpInfo->getEntityId();
                $idpList[$entityId] = [
                    'display_name' => $idpInfo->getDisplayName(),
                ];

                $keywordList = $idpInfo->getKeywords();
                if (0 !== \count($keywordList)) {
                    $idpList[$entityId]['keyword_list'] = $idpInfo->getKeywords();
                }
            }
        } catch (RuntimeException $e) {
            \error_log('ERROR: '.$e->getMessage());
        }
    }

    return $idpList;
}

function fetchFeideIdpList($feideSpUrl)
{
    $idpList = [];
    $locationList = [];
    $feideLocalFile = \dirname(__DIR__).'/cache/'.\urlencode($feideSpUrl);
    if (!@\file_exists($feideLocalFile)) {
        $ch = \curl_init($feideSpUrl);
        \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        \curl_setopt($ch, CURLOPT_COOKIEFILE, '');
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $hdr) use (&$locationList) {
            if (0 === \stripos($hdr, 'Location')) {
                $locationList[] = $hdr;
            }

            return \strlen($hdr);
        });

        \curl_exec($ch);
        // take the last URL redirect
        $lastUrl = \array_pop($locationList);
        $lastUrl = \trim(\substr($lastUrl, \strpos($lastUrl, ':') + 1));
        // make it show the "WAYF"
        $lastUrl .= '&selectorg=change_org';
        \curl_setopt($ch, CURLOPT_URL, $lastUrl);
        $htmlPage = \curl_exec($ch);
        if (false === @\file_put_contents($feideLocalFile, $htmlPage)) {
            throw new RuntimeException(\sprintf('unable to write "%s"', $feideSpUrl));
        }
    }
    $htmlPage = \file_get_contents($feideLocalFile);
    // find the "option" list in the HTML page
    $doc = new DOMDocument();
    @$doc->loadHTML($htmlPage);
    $x = new DOMXPath($doc);
    $dnl = $x->query('//select[@id="org_selector"]//option');
    foreach ($dnl as $dn) {
        $id = $dn->getAttribute('value');
        if (empty($id)) {
            continue;
        }
        $dataOrg = \json_decode(\html_entity_decode($dn->getAttribute('data-org')), true);
        $idpList[$id] = [
            'display_name' => $dataOrg['name'],
        ];
    }

    return $idpList;
}

function rewriteSecureInternet($authTemplateMapping)
{
    $jsonData = \json_decode(\file_get_contents('secure_internet.json'), true);
    $outputData = [];
    foreach ($jsonData['instances'] as $instance) {
        $d = [
            'server_type' => 'secure_internet',
            'base_url' => $instance['base_uri'],
            'public_key_list' => $instance['public_key_list'],
            'country_code' => ccRewrite($instance['base_uri']),
        ];
        if (\array_key_exists('support_contact', $instance)) {
            $d['support_contact'] = $instance['support_contact'];
        }
        if(array_key_exists($instance['base_uri'], $authTemplateMapping)) {
            $d['authentication_url_template'] = $authTemplateMapping[$instance['base_uri']];
        }
        $outputData[] = $d;
    }

    return $outputData;
}

function rewriteInstituteAccess(array $keywordMapping)
{
    $jsonData = \json_decode(\file_get_contents('institute_access.json'), true);
    $outputData = [];
    foreach ($jsonData['instances'] as $instance) {
        $d = [
            'server_type' => 'institute_access',
            'base_url' => $instance['base_uri'],
            'display_name' => $instance['display_name'],
        ];
        if (\array_key_exists('support_contact', $instance)) {
            $d['support_contact'] = $instance['support_contact'];
        }
        if(array_key_exists($instance['base_uri'], $keywordMapping)) {
            $d['keyword_list'] = $keywordMapping[$instance['base_uri']];
        }
        $outputData[] = $d;
    }

    return $outputData;
}
