<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\MetadataParserAll;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server
@\mkdir(\dirname(__DIR__).'/cache', 0711, true);
@\mkdir(\dirname(__DIR__).'/output', 0711, true);

$orgList = ['orgList' => []];
$idpServerMapping = [];
$mappingData = \json_decode(\file_get_contents('mapping.json'), true);
foreach ($mappingData as $baseUrl => $instanceData) {
    $metadataUrlList = $instanceData['metadata_url'];
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
                    $orgList['orgList'][] = [
                        'displayName' => $idpInfo->getDisplayName(),
                        'orgId' => $entityId,
                        'keywords' => $idpInfo->getKeywords(),
                    ];
                    if (!\array_key_exists($entityId, $idpServerMapping)) {
                        $idpServerMapping[$entityId] = [];
                    }
                    $idpServerMapping[$entityId][] = $instanceData;
                }
            } catch (RuntimeException $e) {
                \error_log('ERROR: '.$e->getMessage());
            }
        }
    }
}

\file_put_contents('output/org_list.json', \json_encode($orgList, JSON_PRETTY_PRINT));
foreach ($idpServerMapping as $idpEntityId => $instanceData) {
    \file_put_contents('output/'.\urlencode($idpEntityId).'.json', \json_encode($instanceData, JSON_PRETTY_PRINT));
}
