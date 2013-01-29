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
			->scalarNode('password_type')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
