<?php
namespace Gorg\Bundle\LdapOrmBundle\Tests\Entity;

use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Attribute;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ObjectClass;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Dn;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\Sequence;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\ArrayField;
use Gorg\Bundle\LdapOrmBundle\Annotation\Ldap\DnPregMatch;

/**
 * Class for represent Account 
 * 
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 * @ObjectClass("Account")
 * @Dn("hruid={{ entity.uid }},{% for entite in entity.entities %}ou={{ entite }},{% endfor %}{{ baseDN }}")
 */
class Account
{
    /**
     * @Attribute("uid")
     */
    private $uid;

    /**
     * @Attribute("firstname")
     */
    private $firstname;

    /**
     * @Attribute("nom")
     */
    private $lastname;

    /**
     * @Attribute("alias")
     * @ArrayField()
     */
    private $alias;

    /**
     * @DnPregMatch("/ou=([a-zA-Z0-9\.]+)/")
     */
    private $entities = array("accounts");

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function setFirstname($firstname)
    {
        $this->firstname=$firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    public function getHruid()
    {
        return $this->hruid;
    }

    public function setHruid($hruid)
    {
        $this->hruid=$hruid;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * For password use sha1 php function in base16 encoding
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getAlias()
    {
        return $this->alias;
    }

    public function setAlias($alias)
    {
        $this->alias = $alias;
    }

    public function setEntities($entities)
    {
        $this->entities = $entities;
    }

    public function getEntities()
    {
        return $this->entities;
    }
}
