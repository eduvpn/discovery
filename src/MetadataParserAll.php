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

            // add the display name also to the keywords to help with search
            $keywords = $this->getKeywords($domElement);
            $displayName = $this->getDisplayName($domElement);
            if (null !== $displayName) {
                $keywords = \array_unique(\array_merge($keywords, \explode(' ', $displayName)));
            }

            // "array_values" is required to have a flat array, as array_unique
            // preserves keys...
            $keywords = \array_values($keywords);

            $idpInfoList[] = new IdpInfoSimple(
                $entityId,
                $keywords,
                $displayName
            );
        }

        return $idpInfoList;
    }

    /**
     * @return array<string>
     */
    private function getKeywords(DOMElement $domElement)
    {
        $keyWordsList = [];
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:Keywords[@xml:lang="en"]', $domElement);
        for ($i = 0; $i < $domNodeList->length; ++$i) {
            $keywordsNode = $domNodeList->item($i);
            if (null !== $keywordsNode) {
                $keyWordsList = \array_merge(\explode(' ', $keywordsNode->textContent));
            }
        }

        return \array_unique($keyWordsList);
    }

    /**
     * @return string|null
     */
    private function getDisplayName(DOMElement $domElement)
    {
        $domNodeList = $this->xmlDocument->domXPath->query('md:Extensions/mdui:UIInfo/mdui:DisplayName[@xml:lang="en"]', $domElement);
        if (0 === $domNodeList->length) {
            return null;
        }

        if (null === $displayNameNode = $domNodeList->item(0)) {
            return null;
        }

        return $displayNameNode->textContent;
    }
}
