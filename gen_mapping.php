<?php

$secint = json_decode(file_get_contents('secure_internet.json'), true);
$insacc = json_decode(file_get_contents('institute_access.json'), true);
$outputData =[];

foreach($secint['instances'] as $instance) {
    $baseUri = $instance['base_uri'];
    unset($instance['base_uri']);
    $instance['metadata_url'] = [];
    if(false !== strpos($baseUri, 'eduvpn.org/')) {
        // dutch server
        $instance['metadata_url'][] = sprintf('https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=%ssaml', $baseUri);
    }
    $instance['server_type'] = "secure_internet";
    $outputData[$baseUri] = $instance;
}

foreach($insacc['instances'] as $instance) {
    $baseUri = $instance['base_uri'];
    unset($instance['base_uri']);
    $instance['metadata_url'] = [];
    if(false !== strpos($baseUri, 'eduvpn.nl/')) {
        // dutch server
        $instance['metadata_url'][] = sprintf('https://engine.surfconext.nl/authentication/proxy/idps-metadata?sp-entity-id=%ssaml', $baseUri);
    }
    $instance['server_type'] = "institute_access";
    $outputData[$baseUri] = $instance;
}

echo json_encode($outputData);
