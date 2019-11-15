<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server

$orgList = ['orgList' => []];

$idpServerMapping = [];

$mappingData = \json_decode(\file_get_contents('mapping.json'), true);
foreach ($mappingData as $baseUrl => $instanceData) {
    $metadataUrlList = $instanceData['metadata_url'];
    if (0 !== \count($metadataUrlList)) {
        foreach ($metadataUrlList as $metadataUrl) {
            try {
                $md = new MetadataParserAll($metadataUrl);
                $idpInfoList = $md->get();
                foreach ($idpInfoList as $idpInfo) {
                    $entityId = $idpInfo->getEntityId();
                    $orgList['orgList'][] = [
                        'displayName' => $idpInfo->getDisplayName(),
                        'orgId' => $entityId,
                        'keywords' => $idpInfo->getKeywords(),
                    ];
                    if (!\array_key_exists($entityId, $idpServerMapping)) {
                        $idpServerMapping[$entityId] = [];
                    }
                    $idpServerMapping[$entityId][] = $baseUrl;
                }
            } catch (RuntimeException $e) {
                \error_log('ERROR: '.$e->getMessage());
            }
        }
    }
}

\file_put_contents('org_list.json', \json_encode($orgList, JSON_PRETTY_PRINT));

//echo \json_encode($idpServerMapping);
