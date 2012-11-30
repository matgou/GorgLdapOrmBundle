<?php

namespace Gorg\Bundle\LdapOrmBundle\Annotation\Ldap;

/**
 * Annotation to tell attribute is multi-valued
 * 
 * @Annotation
 * @author Mathieu GOULIN <mathieu.goulin@gadz.org>
 */
final class ArrayField
{
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
}
