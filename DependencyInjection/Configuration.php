<?php

namespace Gorg\Bundle\LdapOrmBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('gorg_ldap_orm');

        $rootNode
            ->children()
                ->arrayNode('connection')->isRequired()
                    ->children()
                        ->scalarNode('uri')->isRequired()->cannotBeEmpty()->end()
                        ->booleanNode('use_tls')->defaultFalse()->end()
                        ->scalarNode('bind_dn')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
                ->arrayNode('ldap')->isRequired()
                    ->children()
                        ->scalarNode('base_dn')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
