<?php

require_once \dirname(__DIR__).'/src/autoload.php';

use VPN\Discovery\MetadataParserAll;

@\mkdir(dirname(__DIR__).'/cache');
@\mkdir(dirname(__DIR__).'/out');

$unixTime = time();

$serverList = json_decode(file_get_contents('server_list.json'), true)['server_list'];

\file_put_contents(
    'out/organization_list.json',
    \json_encode(
        [
            'v' => $unixTime,
            'organization_list' => getOrganizationListFromServerList($serverList),
        ],
        JSON_UNESCAPED_SLASHES
    )
);

// add timestamp to server_list file, remove the "private" entries and write 
// to out/
foreach($serverList as $k => $serverInfo) {
    if(isset($serverInfo['_metadata_url_list'])) {
        unset($serverList[$k]['_metadata_url_list']);
    }
    if(isset($serverInfo['_is_feide_sp'])) {
        unset($serverList[$k]['_is_feide_sp']);
    }
}

\file_put_contents(
    'out/server_list.json',
    \json_encode(
        [
            'v' => $unixTime,
            'server_list' => $serverList,
        ],
        JSON_UNESCAPED_SLASHES
    )
);

function getOrganizationListFromServerList(array $serverList)
{
    $orgInfo = [];
    foreach ($serverList as $serverInfo) {
        if (isset($serverInfo['_is_feide_sp']) && $serverInfo['_is_feide_sp']) {
            // scrape the Feide IdP WAYF
            $feideIdpList = fetchFeideIdpList($serverInfo['base_url']);
            foreach ($feideIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'secure_internet_home' => $serverInfo['base_url'],
                    ];
                    if (\array_key_exists('keyword_list', $idpInfo)) {
                        $orgInfo[$orgId]['keyword_list'] = $idpInfo['keyword_list'];
                    }
                }
            }
        }
        if (isset($serverInfo['_metadata_url_list']) && 0 !== \count($serverInfo['_metadata_url_list'])) {
            // extract the IdPs from the SAML metadata
            $samlIdpList = fetchSamlMetadataIdpList($serverInfo['_metadata_url_list']);
            foreach ($samlIdpList as $orgId => $idpInfo) {
                if (!\array_key_exists($orgId, $orgInfo)) {
                    $orgInfo[$orgId] = [
                        'display_name' => $idpInfo['display_name'],
                        'org_id' => $orgId,
                        'secure_internet_home' => $serverInfo['base_url'],
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

function fetchSamlMetadataIdpList(array $metadataUrlList)
{
    $idpList = [];
    foreach ($metadataUrlList as $metadataUrl) {
        try {
            $metadataLocalFile = \dirname(__DIR__).'/cache/'.\urlencode($metadataUrl);
            if (!@\file_exists($metadataLocalFile)) {
                $ch = \curl_init($metadataUrl);
                \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if(false === $metadataContent = curl_exec($ch)) {
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
