<?php

namespace Gorg\Bundle\LdapOrmBundle\Repository;

use Gorg\Bundle\LdapOrmBundle\Ldap\LdapEntityManager;
use Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection;
use Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter;

/**
 * Repository for fetching ldap entity
 */
class Repository
{
    protected $em, $it;
    private $class;
    private $entityName;

    /**
     * Build the ldap repository
     * 
     * @param LdapEntityManager $em
     * @param ReflectionClass   $reflectorClass
     */
    public function __construct(LdapEntityManager $em, ClassMetaDataCollection $class)
    {
        $this->em = $em;
        $this->class = $class;
        $this->entityName = $class->name;
    }

    /**
     * Adds support for magic finders.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return array|object The found entity/entities.
     * @throws BadMethodCallException  If the method called is an invalid find* method
     *                                 or no find* method at all and therefore an invalid
     *                                 method call.
     */
    public function __call($method, $arguments)
    {
        switch (true) {
            case (0 === strpos($method, 'findBy')):
                $by = lcfirst(substr($method, 6));
                $method = 'findBy';
		if($this->class->getMeta($by) == null) {
                    if($this->class->isArrayOfLink($by . 's')  == null) {
                        throw new \BadMethodCallException("No sutch ldap attribute $by in $this->entityName");
                    } else {
                        $by = $by . 's';
                        $method = 'findInArray';
                    }
                }
                break;

            case (0 === strpos($method, 'findOneBy')):
                $by = lcfirst(substr($method, 9));
                if($this->class->getMeta($by) == null) {
                    throw new \BadMethodCallException("No sutch ldap attribute $by in $this->entityName");
                }
                $method = 'findOneBy';
                break;

            default:
                throw new \BadMethodCallException(
                    "Undefined method '$method'. The method name must start with ".
                    "either findBy or findOneBy!"
                );
        }

        return $this->$method($by,$arguments[0]);
    }
    
    /**
     * Returns an Ldap filters, generic (ie : all) if no argument given
     * or with an attribute and its value specified
     *
     * Also saves previously used filters objects to prevent excessive memory footprint usage
     * 
     * @param type $varname
     * @param type $value
     * @return \Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter 
     */
    private function getFilter($varname = false, $value = false) {
        static $allFilters = array();
        if ($varname === false) {
            $attribute = 'objectClass';
            $value = $this->class->getObjectClass();
        }
        else {
            $attribute = $this->class->getMeta($varname);
        }
        if (!isset($allFilters[$key = base64_encode($attribute . $value)])) {
            $allFilters[$key] = new LdapFilter(array($attribute => $value));
        }
        return $allFilters[$key];
    }

    /**
     * Returns list of object 
     * 
     */
    public function findAll()
    {  
        return $this->em->retrieve($this->getFilter(), $this->entityName);
    }
    
    /**
     * Uses the new Iterator in LdapEntityManager to return the first element of a search
     * 
     * Returns false if there are no more objects in the iterator
     */            
    public function itFindFirst($varname=false, $value=false) {
        if (empty($this->it)) {
            $this->it = $this->em->getIterator($this->getFilter($varname, $value), $this->entityName);
        }
        return $this->it->first();
    }

    /**
     * Uses the new Iterator in LdapEntityManager to return the next element of a search
     * 
     * Returns false if there are no more objects in the iterator
     */            
    public function itGetNext($varname=false, $value=false) {
        if (empty($this->it)) {
            $this->it = $this->em->getIterator($this->getFilter($varname, $value), $this->entityName);
        }
        return $this->it->next();
    }

    /**
     * Verify that we are at the beggining of the iterator
     *
     * @return boolean 
     */
    public function itBegins() {
        return isset($this->it) ? $this->it->isFirst() : false;
    }

    /**
     * Verify that we are at the end of the iterator
     *
     * @return boolean 
     */
    public function itEnds() {
        return isset($this->it) ? $this->it->isLast() : false;
    }
    
    /**
     * Removes an iterator 
     */
    public function itReset() {
        unset($this->it);
    }

    /**
     * Returns list of object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findBy($varname, $value)
    {
        return $this->em->retrieve(
            $this->getFilter($varname, $value),
            $this->entityName
        );
    }

    /**
     * Return an object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findOneBy($varname, $value)
    {
        $arrayOfEntity = $this->em->retrieve($this->getFilter($varname, $value), $this->entityName, 1);
        if(isset($arrayOfEntity[0]))
        {
            return $arrayOfEntity[0];
        }
        return null;
    }

    /**
     * Return an object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findInArray($varname, $value) {
        return $this->em->retrieve($this->getFilter($varname, $this->em->buildEntityDn($value)), $this->entityName);
    }
}
