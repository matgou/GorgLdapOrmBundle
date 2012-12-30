<?php
namespace Gorg\Bundle\LdapOrmBundle\Tests\Functional;

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel {
	public function registerBundles() {
		return array (
			new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
			new \Symfony\Bundle\MonologBundle\MonologBundle(),
                        new \Symfony\Bundle\TwigBundle\TwigBundle(),
    			new \LK\TwigstringBundle\LKTwigstringBundle(),
			new \Gorg\Bundle\LdapOrmBundle\GorgLdapOrmBundle(),
		);
	}

	public function registerContainerConfiguration(LoaderInterface $loader) {
		$loader->load(__DIR__.'/config/minimal.yml');
	}
}
