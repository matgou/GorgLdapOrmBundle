<?php

namespace Gorg\Bundle\LdapOrmBundle\Mapping;


class ClassMetaDataCollection
{
    private $metadatas;
    private $repository;
    public $name;
    public $arrayOfLink;
    public $sequences;
    public $dnRegex;
    public $parentLink;
    public $objectClass;

    public function __construct()
    {
        $this->metadatas        = array();
        $this->reverseMetadatas = array();
        $this->arrayOfLink      = array();
        $this->dnRegex          = array();
        $this->parentLink       = array();
    }

    public function setObjectClass($objectClass) {
        $this->objectClass = $objectClass;
    }

    public function getObjectClass() {
        return $this->objectClass;
    }

    public function getKey($value) 
    {
        if(isset($this->reverseMetadatas[$value])) {
            return $this->reverseMetadatas[$value];
        }
        return null;
    }
    
    public function addMeta($key, $value)
    {
        $this->metadatas[$key] = $value;
        $this->reverseMetadatas[$value] = $key;
    }
    
    public function getMeta($key)
    {
        if(isset($this->metadatas[$key])) {
            return $this->metadatas[$key];
        }
        return null;
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

    public function addParentLink($key, $dn)
    {  
        $this->parentLink[$key] = $dn;
    }

    public function getParentLink()
    {  
        return $this->parentLink;
    }

    public function addRegex($key, $regex)
    {
        $this->dnRegex[$key] = $regex;
    }

    public function getDnRegex()
    {
        return $this->dnRegex;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function setRepository($repository)
    {
        $this->repository = $repository;
    }
}
