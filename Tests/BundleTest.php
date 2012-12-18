<?php
class BundleTest extends PHPUnit_Framework_TestCase {
	// Ensure that the GorgLdapOrmBundle.php file contains the GorgLdapOrmBundle class
	public function testBundleFileExists () {
		$this->assertFileExists (__DIR__ . '/../GorgLdapOrmBundle.php');
	}

	public function testBundleFileContainsBundleClass () {
		$className = 'Gorg\\Bundle\\LdapOrmBundle\\GorgLdapOrmBundle';

		// Attempt to load the class without loading the file
		$this->assertFalse (class_exists ($className, FALSE));

		// Attempt to load the class after loading the file
		require_once __DIR__ . '/../GorgLdapOrmBundle.php';
		$this->assertTrue (class_exists ($className, FALSE));
	}

	public function testBundleClassIsABundle () {
		$bundle = new Gorg\Bundle\LdapOrmBundle\GorgLdapOrmBundle();
		$this->assertTrue (is_a ($bundle, 'Symfony\Component\HttpKernel\Bundle\Bundle'));
	}
}
