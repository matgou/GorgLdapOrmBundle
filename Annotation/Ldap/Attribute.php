<?php

namespace Gorg\Bundle\LdapOrmBundle\Annotation\Ldap;

/**
 * Annotation to describe an Ldap objectClass
 * 
 * @Annotation
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
final class Attribute
{
    private $name;

    /**
     * Build the Attribute object
     * 
     * @param array $data
     * 
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        // Traitement des données de l'annotation
        if (isset($data['value'])) {
            $this->name = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * Return the name of the Attribute
     * 
     *  @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the Attribute's name
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
