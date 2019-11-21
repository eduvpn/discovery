<?php

namespace VPN\Discovery;

use DOMElement;
use RuntimeException;
use VPN\Discovery\Exception\MetadataParserException;

class MetadataParserAll
{
    /** @var XmlDocument */
    private $xmlDocument;

    /**
     * @param string $metadataFile
     */
    public function __construct($metadataFile)
    {
        if (false === $xmlData = @\file_get_contents($metadataFile)) {
            throw new RuntimeException(\sprintf('unable to read file "%s"', $metadataFile));
        }

        $this->xmlDocument = XmlDocument::fromMetadata(
            $xmlData,
            false
        );
    }

    /**
     * @return array<IdpInfoSimple>
     */
    public function get()
    {
        $xPathQuery = '//md:EntityDescriptor/md:IDPSSODescriptor';
        $domNodeList = $this->xmlDocument->domXPath->query($xPathQuery);
        $idpInfoList = [];
        for ($i = 0; $i < $domNodeList->length; ++$i) {
            $domElement = $domNodeList->item($i);
            $entityId = $this->xmlDocument->domXPath->evaluate('string(parent::node()/@entityID)', $domElement);
            if (!($domElement instanceof DOMElement)) {
                throw new MetadataParserException(\sprintf('element "%s" is not an element', $xPathQuery));
            }

            $keywords = $this->getKeywords($domElement);
            $displayName = $this->getDisplayName($domElement);
            $idpInfoList[] = new IdpInfoSimple(
                $entityId,
                $keywords,
                $displayName
            );
        }

        return $idpInfoList;
    }

    /**
     * @return array<string,string>
     */
    private function getKeywords(DOMElement $domElement)
    {
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:Keywords', $domElement);
        if (0 === $domNodeList->length) {
            return [];
        }
        $keywordList = [];
        foreach ($domNodeList as $domNode) {
            if (null === $xmlLang = $domNode->getAttribute('xml:lang')) {
                $xmlLang = 'en';
            }
            $keywordList[$xmlLang] = $domNode->textContent;
        }

        return $keywordList;
    }

    /**
     * @return array<string,string>|null
     */
    private function getDisplayName(DOMElement $domElement)
    {
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:DisplayName', $domElement);
        if (0 === $domNodeList->length) {
            return null;
        }

        $languageList = [];
        foreach ($domNodeList as $domNode) {
            if (null === $xmlLang = $domNode->getAttribute('xml:lang')) {
                $xmlLang = 'en';
            }
            $languageList[$xmlLang] = $domNode->textContent;
        }

        return $languageList;
    }
}
