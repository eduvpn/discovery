<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

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

            // extract all entityIDs from the metadata
            if (false === $xml = @\simplexml_load_string($metadataFileContent)) {
                echo \sprintf('unable to read metadata from "%s"', $metadataUrl).PHP_EOL;
                continue;
            }

            $idpEntityIDs = [];
            $entityDescriptors = $xml->xpath('//md:EntityDescriptor');
            foreach ($entityDescriptors as $entityDescriptor) {
                $ssoCount = \count($entityDescriptor->xpath('md:IDPSSODescriptor'));
                if (0 !== $ssoCount) {
                    $idpEntityIDs[] = (string) $entityDescriptor['entityID'];
                }
            }

            // add to 'global' mapping
            foreach ($idpEntityIDs as $idpEntityId) {
                if (!\array_key_exists($idpEntityId, $idpServerMapping)) {
                    $idpServerMapping[$idpEntityId] = [];
                }
                $idpServerMapping[$idpEntityId][] = $baseUrl;
            }
        }
    }
}

echo \json_encode($idpServerMapping);
