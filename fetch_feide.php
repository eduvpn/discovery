<?php

$noServerList = [
    'https://guest.eduvpn.no/',
    'https://eduvpn.unit.no/',
    'https://eduvpn.uninett.no/',
];

$serverIdpMapping = [];

foreach ($noServerList as $noServer) {
    $locationList = [];
    $ch = \curl_init($noServer);
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
        if (!\array_key_exists($id, $serverIdpMapping)) {
            $dataOrg = \json_decode(\html_entity_decode($dn->getAttribute('data-org')), true);
            $serverIdpMapping[$id] = [
                'display_name' => $dataOrg['name'],
                'base_url_list' => [],
            ];
        }
        $serverIdpMapping[$id]['base_url_list'][] = $noServer;
    }
}

echo \json_encode($serverIdpMapping);
