<?php
// @codingStandardsIgnoreFile
// @codeCoverageIgnoreStart
// this is an autogenerated file - do not edit
spl_autoload_register(
    function($class) {
        static $classes = null;
        if ($classes === null) {
            $classes = array(
                'vpn\\discovery\\exception\\metadataparserexception' => '/Exception/MetadataParserException.php',
                'vpn\\discovery\\exception\\xmldocumentexception' => '/Exception/XmlDocumentException.php',
                'vpn\\discovery\\idpinfosimple' => '/IdpInfoSimple.php',
                'vpn\\discovery\\metadataparserall' => '/MetadataParserAll.php',
                'vpn\\discovery\\xmldocument' => '/XmlDocument.php'
            );
        }
        $cn = strtolower($class);
        if (isset($classes[$cn])) {
            require __DIR__ . $classes[$cn];
        }
    },
    true,
    false
);
// @codeCoverageIgnoreEnd


