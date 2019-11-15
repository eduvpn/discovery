<?php

namespace VPN\Discovery;

class IdpInfoSimple
{
    /** @var string */
    private $entityId;

    /** @var array<string> */
    private $keywords;

    /** @var string|null */
    private $displayName;

    /**
     * @param string        $entityId
     * @param array<string> $keywords
     * @param string|null   $displayName
     */
    public function __construct($entityId, array $keywords, $displayName)
    {
        $this->entityId = $entityId;
        $this->keywords = $keywords;
        $this->displayName = $displayName;
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return array<string>
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @return string|null
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }
}
