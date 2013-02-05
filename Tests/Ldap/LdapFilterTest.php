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

use Gorg\Bundle\LdapOrmBundle\Tests\Functional\AppKernel;
use Gorg\Bundle\LdapOrmBundle\Ldap\Filter\LdapFilter;
use Gorg\Bundle\LdapOrmBundle\Exception\Filter\InvalidLdapFilterException;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class LdapFilterTest extends PHPUnit_Framework_TestCase {
    private $app;

    private $container;

    public function setUp()
    {
        $this->app = new AppKernel('test', true);
        $this->app->boot();
        $this->container = $this->app->getContainer();
    }

    /**
     * Test creation of an empty filter
     */
    public function testEmptyFilter()
    {
        $filter = new LdapFilter(array());
        $this->assertInstanceOf('Gorg\\Bundle\\LdapOrmBundle\\Ldap\\Filter\\LdapFilter', $filter);
        $this->assertEquals($filter->format(), 'objectclass=*');
    }

    /**
     * Test creation of ilter with one attribute 
     */
    public function testSingleAttributeFilter()
    {
        $filter = new LdapFilter(array(
            'foo' => 'bar',
        ));
        $this->assertInstanceOf('Gorg\\Bundle\\LdapOrmBundle\\Ldap\\Filter\\LdapFilter', $filter);
        $this->assertEquals($filter->format(), '(foo=bar)');
    }

    /**
     * Test creation of filter with many attribute
     */
    public function testMultiAttributeFilter()
    {
        $filter = new LdapFilter(array(
            'foo1' => 'bar',
            'foo2' => 'bar2',
             )
        );
        $this->assertInstanceOf('Gorg\\Bundle\\LdapOrmBundle\\Ldap\\Filter\\LdapFilter', $filter);
        $this->assertEquals($filter->format(), '(&(foo1=bar)(foo2=bar2))');
    }

    /**
     * Test creation of filter with many attribute
     */
    public function testMultiAttributeOrFilter()
    {
        $filter = new LdapFilter(array(
            'foo1' => 'bar',
            'foo2' => 'bar2',
             ),
             'OR'
        );
        $this->assertInstanceOf('Gorg\\Bundle\\LdapOrmBundle\\Ldap\\Filter\\LdapFilter', $filter);
        $this->assertEquals($filter->format(), '(|(foo1=bar)(foo2=bar2))');
    }

    /**
     * Test creation of filter with many attribute
     */
    public function testMultiAttributeAndFilter()
    {
        $filter = new LdapFilter(array(
            'foo1' => 'bar',
            'foo2' => 'bar2',
             ),
             'AND'
        );
        $this->assertInstanceOf('Gorg\\Bundle\\LdapOrmBundle\\Ldap\\Filter\\LdapFilter', $filter);
        $this->assertEquals($filter->format(), '(&(foo1=bar)(foo2=bar2))');
    }

    /**
     * Test creation of invalid filter
     * @expectedException     Gorg\Bundle\LdapOrmBundle\Exception\Filter\InvalidLdapFilterException
     * @expectedExceptionMessage The second argument of LdapFilter must be "OR" or "AND" ("test" given)
     */
    public function testInvalidFilter()
    {
        $filter = new LdapFilter(array(), "test");
    }

}
