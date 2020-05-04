<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server
@\mkdir(\dirname(__DIR__).'/cache', 0711, true);
@\mkdir(\dirname(__DIR__).'/output', 0711, true);

$metadataMapping = [
    'https://nl.eduvpn.org/' => ['https://metadata.surfconext.nl/sp/https%253A%252F%252Fnl.eduvpn.org%252Fsaml', 'https://eva-saml-idp.eduroam.nl/simplesamlphp/saml2/idp/metadata.php'],
    'https://eduvpn1.eduvpn.de/' => ['https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml'],
    'https://eduvpn.deic.dk/' => ['https://metadata.wayf.dk/birk-idp.xml'],
];

$feideSpList = [
    'https://guest.eduvpn.no/',
];

$mappingData = getMapping(\json_decode(\file_get_contents('secure_internet.json'), true), $metadataMapping, $feideSpList);

// now retrieve the information of the IdPs through their SAML metadata URLs or
// other means...
$organizationServerList = getOrganizationServerList($mappingData);

// now remove the servers from the entries and put them in separate files
// based on the "orgId"...
writeOrganizationList($organizationServerList);

rewriteSecureInternet();
rewriteInstituteAccess();

function writeOrganizationList(array $organizationServerList)
{
    // we only need to remove server_info_list from the entries
    foreach ($organizationServerList as $k => $v) {
    }
    \file_put_contents('output/organization_list_2.json', \json_encode(['v' => getAtomDate(), 'organization_list' => $organizationServerList], JSON_UNESCAPED_SLASHES));
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

function tplRender($templateName, array $templateVariables = [])
{
    \extract($templateVariables);
    \ob_start();
    include \dirname(__DIR__).'/tpl/'.$templateName.'.tpl.php';

    return \ob_get_clean();
}

/**
 * @return string
 */
function getAtomDate()
{
    $dateTime = new DateTime();

    return $dateTime->format(DateTime::ATOM);
}

function rewriteSecureInternet()
{
    $jsonData = \json_decode(\file_get_contents('secure_internet.json'), true);
    $outputData = [];
    foreach ($jsonData['instances'] as $instance) {
        $d = [
            'base_url' => $instance['base_uri'],
            'display_name' => $instance['display_name'],
            'public_key_list' => $instance['public_key_list'],
        ];
        if (\array_key_exists('support_contact', $instance)) {
            $d['support_contact'] = $instance['support_contact'];
        }
        $outputData[] = $d;
    }

    \file_put_contents('output/server_list_secure_internet.json', \json_encode(['v' => getAtomDate(), 'server_list' => $outputData], JSON_UNESCAPED_SLASHES));
}

function rewriteInstituteAccess()
{
    $jsonData = \json_decode(\file_get_contents('institute_access.json'), true);
    $outputData = [];
    foreach ($jsonData['instances'] as $instance) {
        $d = [
            'base_url' => $instance['base_uri'],
            'display_name' => $instance['display_name'],
        ];
        if (\array_key_exists('support_contact', $instance)) {
            $d['support_contact'] = $instance['support_contact'];
        }
        $outputData[] = $d;
    }

    \file_put_contents('output/server_list_institute_access.json', \json_encode(['v' => getAtomDate(), 'server_list' => $outputData], JSON_UNESCAPED_SLASHES));
}
