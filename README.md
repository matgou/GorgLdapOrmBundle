GorgLdapOrgBundle
===================

GorgLdapOrgBundle is a Interface to retrive, modify or persist entities into an LDAP using php ldap native function.

Installation
------------

1. Add this bundle to your project in composer.json:

    1.1. Plain LdapOrgBundle
        symfony 2.1 uses composer (http://www.getcomposer.org) to organize dependencies:

        ```json
           {
               "require": {
                   "gorg/ldaporm-bundle": "dev-master",
               }
           }
        ```
    1.2. Declare the use of LdapOrgBundle
    
        ``` php
        // application/ApplicationKernel.php
        public function registerBundles()
        {
            return array(
                // ...
                new LK\TwigstringBundle\LKTwigstringBundle(),
                new Gorg\Bundle\LdapOrmBundle\GorgLdapOrmBundle(),
                // ...
            );
        }
        ```
	
    1.3. Configure the ldap parameters
    
        ``` yaml
        gorg_ldap_orm:
            connection:
                uri: ldap://ldap.exemple.com
                use_tls: false
                bind_dn: cn=admin,dc=exemple,dc=com
                password: exemplePassword
            ldap:
                base_dn: dc=exemple,dc=com
        ```

Documentation
-------------

To use the ldapOrmBundle you have to add annotation in entity like this exemple :

    ``` php
    namespace Gorg\Bundle\Application\Entity;

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
    ```

* Attribute : Use this annotation to map a class variable on ldap object field
* ObjectClass : Use this annotation to attribute to a php entity class an ldapObjectClass
* Dn : Use this annotation to build the dn with twig syntaxe
* Sequence : Use this annotation to define a link with an ldap Sequence Object
* ArrayField : This annotation defined an attribute is multi-valued as an array 
* DnPregMatch : This annotation calculate the value of attribute with a regular expression on DN
	
Included Features
-----------------

* manage ldap enity
  * persist entity
  * delete entity
  * retrive entity 
