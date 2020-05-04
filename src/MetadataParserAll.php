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
        $idpInfoList = [];
        $xPathQuery = '//md:EntityDescriptor';
        $domNodeList = $this->xmlDocument->domXPath->query($xPathQuery);
        foreach ($domNodeList as $domElement) {
            if ($this->hideFromDiscovery($domElement)) {
                continue;
            }
            $xPathQuery = 'md:IDPSSODescriptor';
            $domNodeList = $this->xmlDocument->domXPath->query($xPathQuery, $domElement);
            if (0 === $domNodeList->length) {
                continue;
            }

            $domElement = $domNodeList->item(0);
            $entityId = $this->xmlDocument->domXPath->evaluate('string(parent::node()/@entityID)', $domElement);
            if (!($domElement instanceof DOMElement)) {
                throw new MetadataParserException(\sprintf('element "%s" is not an element', $xPathQuery));
            }

            $keywords = $this->getKeywords($domElement);
            if (null === $displayName = $this->getDisplayName($domElement)) {
                $displayName = $entityId;
            }

            $idpInfoList[] = new IdpInfoSimple(
                $entityId,
                $keywords,
                $displayName
            );
        }

        return $idpInfoList;
    }

    /**
     * @return bool
     */
    private function hideFromDiscovery(DOMElement $domElement)
    {
        $aVs = $this->xmlDocument->domXPath->query('md:Extensions/mdattr:EntityAttributes/saml:Attribute[@Name="http://macedir.org/entity-category"]/saml:AttributeValue', $domElement);
        foreach ($aVs as $dE) {
            if ('http://refeds.org/category/hide-from-discovery' === $dE->textContent) {
                return true;
            }
        }

        return false;
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
