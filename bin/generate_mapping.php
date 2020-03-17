<?php

require_once \dirname(__DIR__).'/vendor/autoload.php';

$secint = \json_decode(\file_get_contents('secure_internet.json'), true);
$insacc = \json_decode(\file_get_contents('institute_access.json'), true);
$outputData = [];

// for now we have some hard coded mappings from baseUri to metadata URL here
// so when generating a new file they don't get lost
$manualMapping = [
    'https://nl.eduvpn.org/' => 'https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=https://nl.eduvpn.org/saml',
    'https://eduvpn1.eduvpn.de/' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml',
    'https://eduvpn.deic.dk/' => 'https://metadata.wayf.dk/birk-idp.xml',
];

foreach ($secint['instances'] as $instance) {
    $baseUri = $instance['base_uri'];
    unset($instance['base_uri']);
    unset($instance['logo']);
    $instance['metadata_url'] = [];
    if (\array_key_exists($baseUri, $manualMapping)) {
        $instance['metadata_url'][] = $manualMapping[$baseUri];
    }
    $instance['server_type'] = 'secure_internet';
    $outputData[$baseUri] = $instance;
}

foreach ($insacc['instances'] as $instance) {
    $baseUri = $instance['base_uri'];
    unset($instance['base_uri']);
    unset($instance['logo']);
    $instance['metadata_url'] = [];
    if (false !== \strpos($baseUri, 'eduvpn.nl/')) {
        // dutch server(s)
        $instance['metadata_url'][] = \sprintf('https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=%ssaml', $baseUri);
    }
    $instance['server_type'] = 'institute_access';
    $outputData[$baseUri] = $instance;
}

\file_put_contents('mapping.json', \json_encode($outputData, JSON_UNESCAPED_SLASHES));
