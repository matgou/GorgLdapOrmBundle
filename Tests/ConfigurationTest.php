<?php

use Gorg\Bundle\LdapOrmBundle\Tests\Functional\AppKernel;
use Gorg\Bundle\LdapOrmBundle\DependencyInjection\GorgLdapOrmExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ConfigurationTest extends PHPUnit_Framework_TestCase {
	private $app;

	private $container;

	public function setUp() {
		$this->app = new AppKernel('test', true);
		$this->app->boot();
		$this->container = $this->app->getContainer();
	}

	/**
	 * Ensure the minimal configuration was parsed properly
	 */
	public function testConfigurationParsing () {
		$validConfig = array (
			'connection' => array (
 				'uri' => 'ldap://host.testcompany.com',
				'bind_dn' => 'cn=testuser,dc=testcompany,dc=com',
 				'password' => 'testPassword',
				'use_tls' => false,
			),
			'ldap' => array (
				'base_dn' => 'dc=testcompany,dc=com',
			),
		);

		$config = $this->container->getParameter ('gorg_ldap_orm.config');
		$this->assertEquals ($config, $validConfig);
	}
}
