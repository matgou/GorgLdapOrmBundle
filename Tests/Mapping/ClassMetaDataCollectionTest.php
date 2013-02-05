<?php
namespace Gorg\Bundle\LdapOrmBundle\Tests\Mapping;

use Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection;

/**
 * Testing of entity metadata storage in ClassMetaDataCollection
 */
class ClassMetaDataCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassMetaDataCollection
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new ClassMetaDataCollection;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addArrayField
     */
    public function testAddArrayField()
    {
        $this->object->AddArrayField('test');
        $this->assertTrue($this->object->arrayField['test']);
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::isArrayField
     */
    public function testIsArrayField()
    {
        $this->object->AddArrayField('test');
        $this->assertTrue($this->object->isArrayField('test'));
        $this->assertFalse($this->object->isArrayField('unknown'));
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::setObjectClass
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getObjectClass
     */
    public function testSetObjectClass()
    {
        $this->object->setObjectClass('test');
        $this->assertEquals($this->object->getObjectClass(), 'test');
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addMeta
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getKey
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getMeta
     */
    public function testAddMeta()
    {
        $this->object->addMeta('attributePHP', 'attributeLdap');
        $this->assertEquals($this->object->getKey('attributeLdap'), 'attributePHP');
        $this->assertEquals($this->object->getMeta('attributePHP'), 'attributeLdap');
        $this->assertContains('attributeLdap', $this->object->getMetadatas());
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addArrayOfLink
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::isArrayOfLink
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getArrayOfLinkClass
     */
    public function testAddArrayOfLink()
    {
        $this->object->addArrayOfLink('attributePHP', 'PHPEntityClass');
        $this->assertTrue($this->object->isArrayOfLink('attributePHP'));
        $this->assertEquals('PHPEntityClass', $this->object->getArrayOfLinkClass('attributePHP'));
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addSequence
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::isSequence
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getSequence
     */
    public function testAddSequence()
    {
        $this->object->addSequence('attributePHP', 'SequenceDn');
        $this->assertTrue($this->object->isSequence('attributePHP'));
        $this->assertEquals($this->object->getSequence('attributePHP'), 'SequenceDn');
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addParentLink
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getParentLink
     */
    public function testAddParentLink()
    {
        $this->object->addParentLink('attributePHP', 'Dn');
        $this->assertContains('Dn', $this->object->getParentLink());
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::addRegex
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getDnRegex
     */
    public function testAddRegex()
    {
        $this->object->addRegex('attributePHP', '/regEx/');
        $this->assertContains('/regEx/', $this->object->getDnRegex());
    }

    /**
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::getRepository
     * @covers Gorg\Bundle\LdapOrmBundle\Mapping\ClassMetaDataCollection::setRepository
     */
    public function testGetRepository()
    {
        $this->object->setRepository('testRepo');
        $this->assertEquals($this->object->getRepository(), 'testRepo');
    }
}
