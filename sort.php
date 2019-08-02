<?php

// sort the entries by display_name (en-US)

$jsonData = json_decode(file_get_contents($argv[1]), true);
$instanceList = $jsonData['instances'];

usort($instanceList, function($a, $b) {
    $dA = $a['display_name'];
    if(is_array($dA)) {
        if(array_key_exists('en-US', $dA)) {
            $dA = $dA['en-US'];
        } else {
            $dA = array_values($dA)[0];
        }
    }

    $dB = $b['display_name'];
    if(is_array($dB)) {
        if(array_key_exists('en-US', $dB)) {
            $dB = $dB['en-US'];
        } else {
            $dB = array_values($dB)[0];
        }
    }

    return strcasecmp($dA, $dB);
});

$jsonData['instances'] = $instanceList;
echo json_encode($jsonData);
