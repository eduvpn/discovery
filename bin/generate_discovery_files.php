<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

use VPN\Discovery\Exception\XmlDocumentException;
use VPN\Discovery\XmlDocument;

// generate the eduVPN application discovery files
// we need a mapping from IdP to server

$idpServerMapping = [];

$mappingData = \json_decode(\file_get_contents('mapping.json'), true);
foreach ($mappingData as $baseUrl => $instanceData) {
    $metadataUrlList = $instanceData['metadata_url'];
    if (0 !== \count($metadataUrlList)) {
        foreach ($metadataUrlList as $metadataUrl) {
            if (false === $metadataFileContent = @\file_get_contents($metadataUrl)) {
                echo \sprintf('unable to read "%s"', $metadataUrl).PHP_EOL;
                continue;
            }

            try {
                $xml = XmlDocument::fromMetadata($metadataFileContent, false);
                $idpEntityIDs = [];
                $entityDomNodeList = $xml->domXPath->query('//md:EntityDescriptor');
                foreach ($entityDomNodeList as $entityDomNode) {
                    $domNodeList = $xml->domXPath->query('md:IDPSSODescriptor', $entityDomNode);
                    if (0 !== $domNodeList->length) {
                        $idpEntityIDs[] = (string) $entityDomNode->getAttribute('entityID');
                    }
                }

                // add to 'global' mapping
                foreach ($idpEntityIDs as $idpEntityId) {
                    if (!\array_key_exists($idpEntityId, $idpServerMapping)) {
                        $idpServerMapping[$idpEntityId] = [];
                    }
                    $idpServerMapping[$idpEntityId][] = $baseUrl;
                }
            } catch (XmlDocumentException $e) {
                echo 'XML ERROR: '.$e->getMessage().PHP_EOL;
            }
        }
    }
}

echo \json_encode($idpServerMapping);
