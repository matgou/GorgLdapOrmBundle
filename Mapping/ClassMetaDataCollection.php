<?php

namespace Gorg\Bundle\LdapOrmBundle\Mapping;


class ClassMetaDataCollection
{
    private $metadatas;
    public $name;
    public $arrayOfLink;
    public $sequences;

    public function __construct()
    {
        $this->metadatas = array();
        $this->reverseMetadatas = array();
        $this->arrayOfLink = array();
    }

    public function getKey($value) 
    {
        return $this->reverseMetadatas[$value];
    }
    
    public function addMeta($key, $value)
    {
        $this->metadatas[$key] = $value;
        $this->reverseMetadatas[$value] = $key;
    }
    
    public function getMeta($key)
    {
        return $this->metadatas[$key];
    }
    
    public function getMetadatas()
    {
        return $this->metadatas;
    }
    
    public function setMetadatas($metadatas)
    {
        $this->metadatas = $metadatas;
    }

    public function addArrayOfLink($key, $class)
    {
        $this->arrayOfLink[$key] = $class;
    }

    public function isArrayOfLink($key)
    {
        return isset($this->arrayOfLink[$key]);
    }

    public function getArrayOfLinkClass($key)
    {
        return $this->arrayOfLink[$key];
    }

    public function addSequence($key, $dn)
    {
        $this->sequence[$key] = $dn;
    }

    public function isSequence($key)
    {
        return isset($this->sequence[$key]);
    }

    public function getSequence($key)
    {
        return $this->sequence[$key];
    }
}
