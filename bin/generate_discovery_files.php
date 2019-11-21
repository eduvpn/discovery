<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server
@\mkdir(\dirname(__DIR__).'/cache', 0711, true);
@\mkdir(\dirname(__DIR__).'/output', 0711, true);

$discoBaseUrl = 'https://argon.tuxed.net/fkooman/eduVPN/discovery/v2';
$serverGroupUrl = 'https://argon.tuxed.net/fkooman/eduVPN/discovery/v2/secure_internet.json';

$secureInternetList = ['server_list' => []];
$orgList = ['organization_list' => []];
$idpServerMapping = [];
$mappingData = \json_decode(\file_get_contents('mapping.json'), true);
foreach ($mappingData as $baseUrl => $instanceData) {
    $instanceData['base_url'] = $baseUrl;
    $metadataUrlList = $instanceData['metadata_url'];
    unset($instanceData['metadata_url']);
    $instanceData['logo_uri'] = objectify($instanceData['logo']);
    $instanceData['display_name'] = objectify($instanceData['display_name']);
    unset($instanceData['logo']);
    if ('secure_internet' === $instanceData['server_type']) {
        $instanceData['server_group_url'] = $serverGroupUrl;
        $secureInternetList['server_list'][] = $instanceData;
    }

    unset($instanceData['public_key_list']);
    unset($instanceData['server_type']);

    if (0 !== \count($metadataUrlList)) {
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
                    $orgList['organization_list'][] = [
                        'display_name' => $idpInfo->getDisplayName(),
                        'organization_id' => $entityId,
                        'keyword_list' => $idpInfo->getKeywords(),
                        'server_info_url' => $discoBaseUrl.'/'.encodeEntityId($entityId).'.json',
                    ];
                    if (!\array_key_exists($entityId, $idpServerMapping)) {
                        $idpServerMapping[$entityId] = [
                            'server_list' => [],
                        ];
                    }
                    $idpServerMapping[$entityId]['server_list'][] = $instanceData;
                }
            } catch (RuntimeException $e) {
                \error_log('ERROR: '.$e->getMessage());
            }
        }
    }
}

\file_put_contents('output/organization_list.json', \json_encode($orgList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

for ($i = 0; $i < \count($secureInternetList['server_list']); ++$i) {
    unset($secureInternetList['server_list'][$i]['server_group_url']);
}
\file_put_contents('output/secure_internet.json', \json_encode($secureInternetList, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
foreach ($idpServerMapping as $idpEntityId => $instanceData) {
    \file_put_contents('output/'.encodeEntityId($idpEntityId).'.json', \json_encode($instanceData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * @param string $entityId
 *
 * @return string
 */
function encodeEntityId($entityId)
{
    return \str_replace(
        ['+', '/'],
        ['-', '_'],
        \trim(
            \base64_encode($entityId),
            '='
        )
    );
}

function objectify($input)
{
    if (\is_array($input)) {
        return $input;
    }

    return ['en-US' => $input];
}
