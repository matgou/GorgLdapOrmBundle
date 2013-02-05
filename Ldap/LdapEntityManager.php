<?php
/***************************************************************************
 * Copyright (C) 1999-2012 Gadz.org                                        *
 * http://opensource.gadz.org/                                             *
 *                                                                         *
 * This program is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by    *
 * the Free Software Foundation; either version 2 of the License, or       *
 * (at your option) any later version.                                     *
 *                                                                         *
 * This program is distributed in the hope that it will be useful,         *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of          *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the            *
 * GNU General Public License for more details.                            *
 *                                                                         *
 * You should have received a copy of the GNU General Public License       *
 * along with this program; if not, write to the Free Software             *
 * Foundation, Inc.,                                                       *
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA                   *
 ***************************************************************************/
 
namespace Gorg\Bundle\LdapOrmBundle\Ldap;

use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Attribute;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Dn;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\DnLinkArray;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Sequence;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\DnPregMatch;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ParentDn;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Repository as RepositoryAttribute;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Gorg\Bundle\LdapOrmBundle\Repository\Repository;
use Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter;
use Gorg\Bundle\LdapOrmBundle\Ldap\Converter;
use Gorg\Bundle\LdapOrmBundle\Components\GenericIterator;
use Doctrine\Common\Annotations\Reader;
use Symfony\Bridge\Monolog\Logger;

/**
 * Entity Manager for LDAP
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
class LdapEntityManager
{
    private $uri        	= "";
    private $bindDN     	= "";
    private $password   	= "";
    private $baseDN     	= "";
    private $passwordType 	= "";
    private $useTLS     	= FALSE;

    private $ldapResource;
    private $reader;
    
    private $iterator = Null;

    /**
     * Build the Entity Manager service
     *
     * @param Twig_Environment $twig
     * @param Reader           $reader
     * @param array            $config
     */
    public function __construct(Logger $logger, \Twig_Environment $twig, Reader $reader, $config)
    {
        $this->logger     	= $logger;
        $this->twig       	= $twig;
        $this->uri        	= $config['connection']['uri'];
        $this->bindDN     	= $config['connection']['bind_dn'];
        $this->password   	= $config['connection']['password'];
        $this->baseDN     	= $config['ldap']['base_dn'];
	$this->passwordType 	= $config['ldap']['password_type'];
        $this->useTLS     	= $config['connection']['use_tls'];
        $this->reader     	= $reader;
    }

    /**
     * Connect to GrAM database
     * 
     * @return resource
     */
    private function connect()
    {
        // Don't permit multiple connect() calls to run
        if ($this->ldapResource) {
            return;
        }

        $this->ldapResource = ldap_connect($this->uri);
        ldap_set_option($this->ldapResource, LDAP_OPT_PROTOCOL_VERSION, 3);

        // Switch to TLS, if configured
        if ($this->useTLS) {
            $tlsStatus = ldap_start_tls($this->ldapResource);
            if (!$tlsStatus) {
                throw new \Exception('Unable to enable TLS for LDAP connection.');
            }
            $this->logger->info('TLS enabled for LDAP connection.');
        }

        $r = ldap_bind($this->ldapResource, $this->bindDN, $this->password);
        if($r == null) {
            throw new \Exception('Connexion impossible au serveur ldap ' . $this-uri . ' avec l\'utilisateur ' . $this->bindDN . ' ' . $this->password . '.');
        }
        $this->logger->info('Connexion au serveur ldap ' . $this->uri . ' avec l\'utilisateur ' . $this->bindDN . ' .');
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
        $classAnnotations = $this->reader->getClassAnnotations($r);

        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof RepositoryAttribute) {
                $instanceMetadataCollection->setRepository($classAnnotation->getValue());
            }
            if ($classAnnotation instanceof ObjectClass) {
                $instanceMetadataCollection->setObjectClass($classAnnotation->getValue());
            }
        }

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
                if ($annotation instanceof ParentDn) {
                    $varname=$publicAttr->getName();
                    $instanceMetadataCollection->addParentLink($varname, $annotation->getValue());
                }
                if ($annotation instanceof ArrayField) {
                    $instanceMetadataCollection->addArrayField($varname);
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
            if ($classAnnotation instanceof ObjectClass && ($classAnnotationValue = $classAnnotation->getValue()) !== '' ) {
                array_push($arrayInstance['objectClass'], $classAnnotationValue);
            }
        }

        foreach($instanceMetadataCollection->getMetadatas() as $varname) {
            $getter = 'get' . ucfirst($instanceMetadataCollection->getKey($varname));
            $setter = 'set' . ucfirst($instanceMetadataCollection->getKey($varname));

            $value  = $instance->$getter();
            if($value == null) {
                if($instanceMetadataCollection->isSequence($instanceMetadataCollection->getKey($varname))) {

                    $sequence = $this->renderString($instanceMetadataCollection->getSequence($instanceMetadataCollection->getKey($varname)), array(
                        'entity' => $instance,
                        'baseDN' => $this->baseDN,
                    ));

                    $value = (int) $this->generateSequenceValue($sequence);
                    $instance->$setter($value);
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

            if(is_object($value)) {
                if($value instanceof \DateTime)
                {
                    $arrayInstance[$varname] = Converter::toLdapDateTime($value, false);
                } else {
                    $arrayInstance[$varname] = $this->buildEntityDn($value);
                }
            } elseif(is_array($value) && !empty($value) && isset($value[0]) && is_object($value[0])) {
                    $valueArray = array();
                    foreach($value as $val) {
                        $valueArray[] = $this->buildEntityDn($val);
                    }
                    $arrayInstance[$varname] = $valueArray;
            } elseif(strtolower($varname) == "userpassword") {
                if(!is_array($value)) {
                    if($this->isSha1($value) && $this->passwordType == "sha1") {
                        $hash = pack("H*", $value);
                        $arrayInstance[$varname] = '{SHA}' . base64_encode($hash);
                        $this->logger->info(sprintf("convert %s to %s", $value, $arrayInstance[$varname]));
                    } elseif ($this->passwordType == 'plaintext' {
			$arrayInstance[$varname] = $value;
		    }
                }
            }  else {
                $arrayInstance[$varname] = $value;
            }
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

        $dnModel = '';
        foreach ($classAnnotations as $classAnnotation) {
            if ($classAnnotation instanceof Dn) {
                $dnModel = $classAnnotation->getValue();
                break;
            }
        }

        return $this->renderString($dnModel, array(
                'entity' => $instance,
                'baseDN' => $this->baseDN,
                ));
    }

    /**
     * Persist an instance in Ldap
     * @param unknown_type $instance
     */
    public function persist($instance)
    {
        $arrayInstance= $this->entityToArray($instance);
        $this->logger->info('to array : ' . serialize($arrayInstance));

        $dn = $this->buildEntityDn($instance);

        // test if entity already exist
        if(count($this->retrieveByDn($dn, get_class($instance), 1)) > 0)
        {
            unset($arrayInstance['objectClass']);
            $this->ldapUpdate($dn, $arrayInstance);
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
	$this->deleteByDn($dn, true);
        return;
    }

    /**
     * Delete an entry in ldap by Dn
     * @param string $dn
     */
    public function deleteByDn($dn, $recursive=false)
    {
        // Connect if needed
        $this->connect();

        $this->logger->info('Delete (recursive=' . $recursive . ') in LDAP: ' . $dn );

        if($recursive == false) {
            return(ldap_delete($this->ldapResource, $dn));
        } else {
            //searching for sub entries
            $sr=ldap_list($this->ldapResource, $dn, "ObjectClass=*", array(""));
            $info = ldap_get_entries($this->ldapResource, $sr);

            for($i = 0; $i < $info['count']; $i++) {
                //deleting recursively sub entries
                $result=$this->deleteByDn($info[$i]['dn'], true);
                if(!$result) {
                    //return result code, if delete fails
                    return($result);
                }
            }
            return(ldap_delete($this->ldapResource, $dn));
        }
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
        if($metadata->getRepository()) {
            $repository = $metadata->getRepository();
            return new $repository($this, $metadata);
        }
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
        // Connect if needed
        $this->connect();

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
        // Connect if needed
        $this->connect();

        $this->logger->info('Modify in LDAP: ' . $dn . ' ' . serialize($arrayInstance));
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
    public function retrieveByDn($dn, $entityName, $max = 100, $objectClass = "*")
    {
        // Connect if needed
        $this->connect();

        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $data = array();
        $this->logger->info('Search in LDAP: ' . $dn . ' query (ObjectClass=*)');
        try {
            $sr = ldap_search($this->ldapResource,
                $dn,
                '(ObjectClass=' . $objectClass . ')',
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
        // Connect if needed
        $this->connect();

        $instanceMetadataCollection = $this->getClassMetadata($entityName);

        $data = array();
        $this->logger->info(sprintf("request on ldap root:%s with filter:%s", $this->baseDN, $filter->format('ldap')));
        $sr = ldap_search($this->ldapResource,
            $this->baseDN,
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
    
    public function getIterator(LdapFilter $filter, $entityName) {
        if (empty($this->iterator)) {
            $this->iterator = new LdapIterator($filter, $entityName, $this);
        }
        return $this->iterator;
    }

    private function arrayToObject($entityName, $array)
    {
        $instanceMetadataCollection = $this->getClassMetadata($entityName);
       
        $dn = $array['dn']; 
        $entity = new $entityName();
        foreach($instanceMetadataCollection->getMetadatas() as $varname => $attributes) {
            if($instanceMetadataCollection->isArrayOfLink($varname))
            {
                $entityArray = array();
                if(!isset($array[strtolower($attributes)])) {
                    $array[strtolower($attributes)] = array('count' => 0);
                }
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
                if (!isset($array[strtolower($attributes)])) {
                    continue; // Inutile de continuer si l'attribut n'existe pas dans les données lues
                }
                try {
                    $setter = 'set' . ucfirst($varname);
                    if(strtolower($attributes) == "userpassword") {
                        if(count($array[strtolower($attributes)]) > 2) {
                            unset($array[strtolower($attributes)]["count"]);
                            $password = array();
                            foreach($array[strtolower($attributes)] as $value) {
                                $string = base64_decode($value);
                                $password[] = bin2hex($string);
                            }
                            $entity->$setter($password);
                        } else {
                            $value = str_replace("{SHA}", "", $array[strtolower($attributes)][0]);
                            $string = base64_decode($value);
                            $entity->$setter(bin2hex($string));
                        }
                    } elseif(preg_match('/^\d{14}/', $array[strtolower($attributes)][0])) {
                        $datetime = Converter::fromLdapDateTime($array[strtolower($attributes)][0], false);
                        $entity->$setter($datetime);
                    } elseif ($instanceMetadataCollection->isArrayField($varname)) {
                        unset($array[strtolower($attributes)]["count"]);
                        $entity->$setter($array[strtolower($attributes)]);
                    } else {
                        $entity->$setter($array[strtolower($attributes)][0]);
                    }
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
        if($dn != '') {
            foreach($instanceMetadataCollection->getParentLink() as $varname => $parentClass) {
                $setter = 'set' . ucfirst($varname);
                $parentDn = preg_replace('/^[a-zA-Z0-9]*=[a-zA-Z0-9]*,/', '', $dn);
                $link = $this->retrieveByDn($parentDn, $parentClass);
                if(count($link) > 0) {
                    $entity->$setter($link[0]);
                }
            }
        }

        return $entity;
    }

    private function generateSequenceValue($dn)
    {
        // Connect if needed
        $this->connect();

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

    private function isSha1($str) {
        return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
    }
}


class LdapIterator implements \GenericIterator
{
    private $resource;
    private $result;
    private $pos;
    private $total;

    public function __construct (LdapFilter $filter, $entityName, LdapEntityManager $entityManager) {
        $instanceMetadataCollection = $entityManager->getClassMetadata($entityName);

        $this->resource = $entityManager->ldapResource;
        $this->result = ldap_search($this->resource,
            $entityManager->rootDN,
            $filter->format('ldap'),
            array_values($instanceMetadataCollection->getMetadatas()),
            0
        );
        $this->pos    = 0;
        $this->total  = ldap_count_entries($this->resource, $this->result);
    }
 
    /** Put the position to the first element of the Iteration and returns it.
     */
    public function first() {
        if ($this->result === false) return false;
        $this->pos = 0;
        return ldap_first_entry($this->resource, $this->result);
    }

    /** True if the current element is the first element.
     */
    public function isFirst() {
        return ($this->pos === 0);
    }
    
    /** True if the current element is the last element.
     */
    public function isLast() {
        return ($this->pos === $this->total-1);
    }

    /** Return the number of element of the iterator.
     */
    public function total() {
        return $this->total;
    }

    public function next() {
        if ($this->pos == 0) return $this->first();
        if ($this->result === false) return false;
        $this->pos++;
        return ldap_next_entry($this->resource, $this->result);
    }
    
}
