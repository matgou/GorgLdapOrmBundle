<?php

namespace Gorg\Bundle\LdapOrmBundle\Mapping;


class ClassMetaDataCollection
{
    private $metadatas;
    public $name;

    public function __construct()
    {
        $this->metadatas = array();
        $this->reverseMetadatas = array();
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
}