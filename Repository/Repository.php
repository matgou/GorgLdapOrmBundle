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
    protected $em;
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
     * Return list of object 
     * 
     */
    public function findAll()
    {  
        $filter = new LdapFilter(array(
            'objectClass' => $this->class->getObjectClass(),
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }


    /**
     * Return list of object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findBy($varname, $value)
    {
        $attribute = $this->class->getMeta($varname);
        $filter = new LdapFilter(array(
                $attribute => $value,
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }

    /**
     * Return an object with corresponding varname as Criteria
     * 
     * @param unknown type $varname
     * @param unknown_type $value
     */
    public function findOneBy($varname, $value)
    {
        $attribute = $this->class->getMeta($varname);
        $filter = new LdapFilter(array(
                $attribute => $value,
        ));

        $arrayOfEntity = $this->em->retrieve($filter, $this->entityName, 1);
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
        $attribute = $this->class->getMeta($varname);
        $dnToFind = $this->em->buildEntityDn($value);

        $filter = new LdapFilter(array(
                $attribute => $dnToFind,
        ));
        return $this->em->retrieve($filter, $this->entityName);
    }
}
