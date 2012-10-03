<?php

namespace Gorg\Bundle\LdapOrmBundle\Ldap;

use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Attribute;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Dn;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\DnLinkArray;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Sequence;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\DnPregMatch;
use Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Gorg\Bundle\LdapOrmBundle\Repository\Repository;
use Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter;
use Doctrine\Common\Annotations\Reader;
use Symfony\Bridge\Monolog\Logger;

/**
 * Entity Manager for LDAP
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
class LdapEntityManager
{
    private $host       = "";
    private $port       = "";
    private $bindDN     = "";
    private $passwordDN = "";
    private $rootDN     = "";

    private $ldapResource;
    private $reader;

    /**
     * Build the Entity Manager service
     *
     * @param Twig_Environment $twig
     * @param Reader           $reader
     * @param string           $host
     * @param string           $port
     * @param string           $bindDN
     * @param string           $passwordDN
     */
    public function __construct(Logger $logger, \Twig_Environment $twig, Reader $reader, $host, $port, $bindDN, $passwordDN, $rootDN)
    {
      $this->logger     = $logger;
      $this->twig       = $twig;
      $this->host       = $host;
      $this->port       = intval($port);
      $this->bindDN     = $bindDN;
      $this->passwordDN = $passwordDN;
      $this->rootDN     = $rootDN;
      $this->reader     = $reader;
      $this->connect();
    }

    /**
     * Connect to GrAM database
     * 
     * @return resource
     */
    private function connect()
    {
        $this->ldapResource = ldap_connect($this->host, $this->port);
        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);
        $r = ldap_bind($this->ldapResource, $this->bindDN, $this->passwordDN);
        if($r == null) {
            throw new \Exception('Connexion impossible au serveur ldap ' . $this->host . ':' . $this->port . ' avec l\'utilisateur ' . $this->bindDN . ' ' . $this->passwordDN . '.');
        }
        $this->logger->info('Connexion au serveur ldap ' . $this->host . ':' . $this->port . ' avec l\'utilisateur ' . $this->bindDN . ' .');
        return $r;
    }

    /**
     * Return the class metadata instance
     * 
     * @param string $entityName
     * 
     * @return ClassMetaDataCollection
     */
    private function getClassMetadata($entityName)
    {
        $r = new \ReflectionClass($entityName);
        $instanceMetadataCollection = new ClassMetaDataCollection();
        $instanceMetadataCollection->name = $entityName;

        foreach ($r->getProperties() as $publicAttr) {
            $annotations = $this->reader->getPropertyAnnotations($publicAttr);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof Attribute) {
                    $varname=$publicAttr->getName();
                    $attribute=$annotation->getName();
                    $instanceMetadataCollection->addMeta($varname, $attribute);
                }
                if ($annotation instanceof DnLinkArray) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addArrayOfLink($varname, $annotation->getValue());
                }
                if ($annotation instanceof Sequence) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addSequence($varname, $annotation->getValue());
                }
                if ($annotation instanceof DnPregMatch) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addRegex($varname, $annotation->getValue());
                }
            }
        }

        return $instanceMetadataCollection;
    }

    /**
     * Convert an entity to array using annotation reader
     * 
     * @param unknown_type $instance
     * 
     * @return array
     */
    private function entityToArray($instance)
    {
        $instanceClassName = get_class($instance);
        $arrayInstance=array();

        $r = new \ReflectionClass($instanceClassName);
        $instanceMetadataCollection = $this->getClassMetadata($instance);
        $classAnnotations = $this->reader->getClassAnnotations($r);

        $arrayInstance['objectClass'] = array('top');

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof ObjectClass) {
                array_push($arrayInstance['objectClass'], $classAnnotation->getValue());
            }
        }

        foreach($instanceMetadataCollection->getMetadatas() as $varname) {
            $getter = 'get' . ucfirst($instanceMetadataCollection->getKey($varname));
            $value  = $instance->$getter();
            if($value == null) {
                if($instanceMetadataCollection->isSequence($instanceMetadataCollection->getKey($varname))) {
                    $value = (int) $this->generateSequenceValue($instanceMetadataCollection->getSequence($instanceMetadataCollection->getKey($varname)));
                }
            }
            // Specificity of ldap (incopatibility with ldap boolean)
            if(is_bool($value)) {
                if($value) {
                    $value = "TRUE";
                } else {
                    $value = "FALSE";
                }
            }
            $arrayInstance[$varname] = $value;
        }

        return $arrayInstance;
    }

    private function renderString($string, $vars)
    {
        return $this->twig->render($string, $vars);
    }
    /**
     * Build a DN for an entity with the use of dn annotation
     * 
     * @param unknown_type $instance
     * 
     * @return string
     */
    public function buildEntityDn($instance)
    {
        $instanceClassName = get_class($instance);
        $arrayInstance=array();

        $r = new \ReflectionClass($instanceClassName);
        $instanceMetadataCollection = new ClassMetaDataCollection();
        $classAnnotations = $this->reader->getClassAnnotations($r);

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof Dn) {
                $dnModel = $classAnnotation->getValue();
            }
        }

        return $this->renderString($dnModel, array(
                'entity' => $instance,
                'rootDN' => $this->rootDN,
                ));
    }

    /**
     * Persist an instance in Ldap
     * @param unknown_type $instance
     */
    public function persist($instance)
    {
        $arrayInstance= $this->entityToArray($instance);
        $dn = $this->buildEntityDn($instance);

        // test if entity already exist
        if(count($this->retrieveByDn($dn, get_class($instance), 1)) > 0)
        {
            $arrayInstance['objectClass'] = null;
            $this->ldapUpdate($dn, array_filter($arrayInstance));
            return;
        }
        $this->ldapPersist($dn, $arrayInstance);
        return;
    }

    /**
     * Delete an instance in Ldap
     * @param unknown_type $instance
     */
    public function delete($instance)
    {  
        $dn = $this->buildEntityDn($instance);
        $this->logger->info('Delete in LDAP: ' . $dn );
	ldap_delete($this->ldapResource, $dn);
        return;
    }

    /**
     * Send entity to database
     */
    public function flush()
    {
        return;
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $entityName The name of the entity.
     * 
     * @return EntityRepository The repository class.
     */
    public function getRepository($entityName)
    {
        $metadata = $this->getClassMetadata($entityName);

        return new Repository($this, $metadata);
    }

    /**
     * Persist an array using ldap function
     * 
     * @param unknown_type $dn
     * @param array        $arrayInstance
     */
    private function ldapPersist($dn, Array $arrayInstance)
    {
        $this->logger->info('Insert into LDAP: ' . $dn . ' ' . serialize($arrayInstance));
        ldap_add($this->ldapResource, $dn, $arrayInstance);
    }

    /**
     * Update an object in ldap with array
     *
     * @param unknown_type $dn
     * @param array        $arrayInstance
     */
    private function ldapUpdate($dn, Array $arrayInstance)
    {  
        $this->logger->info('Modify in LDAP: ' . $dn);
        ldap_modify($this->ldapResource, $dn, $arrayInstance);
    }

    /**
     * retrieve object from dn
     *
     * @param string     $dn
     * @param string     $entityName
     * @param integer    $max
     *
     * @return array
     */
    public function retrieveByDn($dn, $entityName, $max = 100)
    {  
        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $data = array();
        $this->logger->info('Search in LDAP: ' . $dn . ' query (ObjectClass=*)');
        try {
            $sr = ldap_search($this->ldapResource,
                $dn,
                '(ObjectClass=*)',
                array_values($instanceMetadataCollection->getMetadatas()),
                0
            );
            $infos = ldap_get_entries($this->ldapResource, $sr);
            foreach ($infos as $entry) {
                if(is_array($entry)) {
                    $data[] = $this->arrayToObject($entityName, $entry);
                }
            }
        } catch(\Exception $e) {
            $data = array();
        }
 
        return $data;
    }

    /**
     * retrieve hruid array from a filter
     * 
     * @param LdapFilter $filter
     * @param string     $entityName
     * @param integer    $max
     * 
     * @return array
     */
    public function retrieve(LdapFilter $filter, $entityName, $max = 100)
    {
        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $data = array();
        $sr = ldap_search($this->ldapResource,
            $this->rootDN,
            $filter->format('ldap'),
            array_values($instanceMetadataCollection->getMetadatas()),
            0
        );
        $infos = ldap_get_entries($this->ldapResource, $sr);
        foreach ($infos as $entry) {
            if(is_array($entry)) {
                $data[] = $this->arrayToObject($entityName, $entry);
            }
        }
        return $data;
    }

    private function arrayToObject($entityName, $array)
    {
        $instanceMetadataCollection = $this->getClassMetadata($entityName);
        
        $entity = new $entityName();
        foreach($instanceMetadataCollection->getMetadatas() as $varname => $attributes) {
            if($instanceMetadataCollection->isArrayOfLink($varname))
            {
                $entityArray = array();
                $linkArray = $array[strtolower($attributes)];
                $count = $linkArray['count'];
                for($i = 0; $i < $count; $i++) {
                    if($linkArray[$i] != null) {
                        $targetArray = $this->retrieveByDn($linkArray[$i], $instanceMetadataCollection->getArrayOfLinkClass($varname), 1);
                        $entityArray[] = $targetArray[0];
                    }
                }
                $setter = 'set' . ucfirst($varname);
                $entity->$setter($entityArray);
            } else {
                try {
                    $setter = 'set' . ucfirst($varname);
                    $entity->$setter($array[strtolower($attributes)][0]);
                } catch (\Exception $e)
                {

                }
           }
        }
        foreach($instanceMetadataCollection->getDnRegex() as $varname => $regex) {
            preg_match_all($regex, $array['dn'], $matches);
            $setter = 'set' . ucfirst($varname);
            $entity->$setter($matches[1]);
        }

        return $entity;
    }

    private function generateSequenceValue($dn)
    {
        $sr = ldap_search($this->ldapResource,
            $dn,
            '(objectClass=integerSequence)'
        );
        $infos = ldap_get_entries($this->ldapResource, $sr);
        $sequence = $infos[0];
        $return = $sequence['nextvalue'][0];
        $newValue = $sequence['nextvalue'][0] + $sequence['increment'][0];
        $entry = array(
            'nextvalue' => array($newValue),
        );
        ldap_modify($this->ldapResource, $dn, $entry);
        return $return;
    }
}
