<?php

namespace Gorg\Bundle\LdapOrmBundle\Annotation\Ldap;

/**
 * Annotation to describe an regex fetching an attribute
 * 
 * @Annotation
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
final class DnPregMatch
{
    private $value;

    /**
     * Build the ObjectClass object
     * 
     * @param array $data
     * 
     * @throws \BadMethodCallException
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }
            $this->$method($value);
        }
    }

    /**
     * Return the value of the objectClass
     * 
     *  @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of the objectClass
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
