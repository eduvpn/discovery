<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server
@\mkdir(\dirname(__DIR__).'/cache', 0711, true);
@\mkdir(\dirname(__DIR__).'/output', 0711, true);

$discoBaseUrl = 'https://argon.tuxed.net/fkooman/eduVPN/discovery/v2';
$discoveryFiles = [
    'secure_internet' => \json_decode(\file_get_contents('secure_internet.json'), true),
    'institute_access' => \json_decode(\file_get_contents('institute_access.json'), true),
];

$metadataMapping = [
    'https://nl.eduvpn.org/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://nl.eduvpn.org/saml',
    'https://eduvpn1.eduvpn.de/' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml',
    'https://eduvpn.deic.dk/' => 'https://metadata.wayf.dk/birk-idp.xml',
    'https://demo.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://demo.eduvpn.nl/saml',
    'https://differ.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://differ.eduvpn.nl/saml',
    'https://egi.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://egi.eduvpn.nl/saml',
    'https://eur.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://eur.eduvpn.nl/saml',
    'https://esciencecenter.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://esciencecenter.eduvpn.nl/saml',
    'https://nikhef.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://nikhef.eduvpn.nl/saml',
    'https://ru.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://ru.eduvpn.nl/saml',
    'https://stc.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://stc.eduvpn.nl/saml',
    'https://surfnet.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://surfnet.eduvpn.nl/saml',
    'https://ut.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://ut.eduvpn.nl/saml',
    'https://hku.eduvpn.nl/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://hku.eduvpn.nl/saml',
];

$feideSpList = [
    'https://guest.eduvpn.no/',
    'https://eduvpn.unit.no/',
    'https://uninett.eduvpn.no/',
];

// merge existing "secure_internet" and "institute_access" files into one and
// augment them with information on how to obtain the list of IdPs that can
// access those servers...
$mappingData = getMapping($discoveryFiles, $metadataMapping, $feideSpList);

// now retrieve the information of the IdPs through their SAML metadata URLs or
// other means...
$organizationServerList = getOrganizationServerList($mappingData, $discoBaseUrl);

// now remove the servers from the entries and put them in separate files
// based on the "orgId"...
writeOrganizationList($organizationServerList);
writeServerFiles($organizationServerList, $discoveryFiles);

// generate HTML
writeOrganizationListHtml($organizationServerList);

function writeServerFiles(array $organizationServerList, array $discoveryFiles)
{
    // only keep "secure_internet" fields we really need
    $peerList = [];
    foreach ($discoveryFiles['secure_internet']['instances'] as $serverInfo) {
        $peerList[] = [
            'base_url' => $serverInfo['base_uri'],
            'display_name' => toLanguageObject($serverInfo['display_name']),
        ];
    }

    foreach ($organizationServerList as $k => $v) {
        $orgId = $v['org_id'];
        $serverList = $v['server_info_list'];
        foreach ($serverList as $k => $v) {
            if ('secure_internet' === $serverList[$k]['server_type']) {
                $serverList[$k]['peer_list'] = $peerList;
            }
            unset($serverList[$k]['server_type']);
            unset($serverList[$k]['metadata_url_list']);
            unset($serverList[$k]['is_feide_sp']);
        }
        \file_put_contents('output/'.encodeId($orgId).'.json', \json_encode(['server_list' => $serverList], JSON_UNESCAPED_SLASHES));
    }
}

function writeOrganizationList(array $organizationServerList)
{
    // we only need to remove server_info from the entries
    foreach ($organizationServerList as $k => $v) {
        unset($organizationServerList[$k]['server_info_list']);
    }

    \file_put_contents('output/organization_list.json', \json_encode(['organization_list' => $organizationServerList], JSON_UNESCAPED_SLASHES));
}

function writeOrganizationListHtml(array $organizationServerList)
{
    $oSL = [];
    foreach ($organizationServerList as $k => $v) {
        // read the JSON file
        $serverJson = \json_decode(\file_get_contents('output/'.$v['server_info']), true);
        unset($v['server_info_list']);
        $v['server_list'] = $serverJson['server_list'];
        $oSL[] = $v;
    }

    \file_put_contents('output/organization_list.html', tplRender('organization_list', ['orgList' => $oSL]));
}

function getOrganizationServerList(array $mappingData, $discoBaseUrl)
{
    $orgInfo = [];
    foreach ($mappingData as $baseUrl => $serverInfo) {
        $serverInfo['base_url'] = $baseUrl;
        if ($serverInfo['is_feide_sp']) {
            // scrape the Feide IdP WAYF
            $feideIdpList = fetchFeideIdpList($baseUrl);
            // XXX saml thing below has exact same code
            foreach ($feideIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'server_info_list' => [],
                        'server_info_url' => $discoBaseUrl.'/'.encodeId($orgId).'.json',    // XXX this one needs to be removed at some point!
                        'server_info' => encodeId($orgId).'.json',
                    ];
                    if (\array_key_exists('keyword_list', $idpInfo)) {
                        $orgInfo[$orgId]['keyword_list'] = $idpInfo['keyword_list'];
                    }
                }
                $orgInfo[$orgId]['server_info_list'][] = $serverInfo;
            }
        }
        if (0 !== \count($serverInfo['metadata_url_list'])) {
            // extract the IdPs from the SAML metadata
            $samlIdpList = fetchSamlMetadataIdpList($serverInfo['metadata_url_list']);
            // XXX feide thing above has exact same code
            foreach ($samlIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'server_info_list' => [],
                        'server_info_url' => $discoBaseUrl.'/'.encodeId($orgId).'.json',    // XXX this one needs to be removed at some point!
                        'server_info' => encodeId($orgId).'.json',
                    ];
                    if (\array_key_exists('keyword_list', $idpInfo)) {
                        $orgInfo[$orgId]['keyword_list'] = $idpInfo['keyword_list'];
                    }
                }
                $orgInfo[$orgId]['server_info_list'][] = $serverInfo;
            }
        }
    }

    return \array_values($orgInfo);
}

function getMapping(array $discoveryFiles, array $metadataMapping, array $feideSpList)
{
    $outputData = [];
    foreach ($discoveryFiles as $k => $v) {
        foreach ($v['instances'] as $instance) {
            $baseUri = $instance['base_uri'];
            $metadataUrlList = [];
            $isFeideSp = false;

            if (\array_key_exists($baseUri, $metadataMapping)) {
                $metadataUrlList[] = $metadataMapping[$baseUri];
            }
            if (\in_array($baseUri, $feideSpList, true)) {
                $isFeideSp = true;
            }
            $outputData[$baseUri] = [
                'server_type' => $k,
                'metadata_url_list' => $metadataUrlList,
                'is_feide_sp' => $isFeideSp,
                'display_name' => toLanguageObject($instance['display_name']),
            ];
        }
    }

    return $outputData;
}

function toLanguageObject($input)
{
    if (\is_array($input)) {
        return $input;
    }

    return ['en' => $input];
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
 * @param string $i
 *
 * @return string
 */
function encodeId($i)
{
    return \str_replace(
        ['+', '/'],
        ['-', '_'],
        \trim(
            \base64_encode($i),
            '='
        )
    );
}
