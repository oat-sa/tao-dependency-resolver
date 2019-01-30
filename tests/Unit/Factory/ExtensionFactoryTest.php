<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Test\Unit\Factory;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Factory\ExtensionFactory;
use PHPUnit\Framework\TestCase;

class ExtensionFactoryTest extends TestCase
{
    /** @var ExtensionFactory */
    private $subject;

    protected function setUp()
    {
        parent::setUp();

        $extensionMap = [
            'extensionName1' => 'repositoryName1',
            'extensionName2' => 'repositoryName2',
        ];

        $this->subject = new ExtensionFactory($extensionMap);
    }

    public function testCreateMappedExtensions()
    {
        $output1 = $this->subject->create('extensionName1');

        $this->assertInstanceOf(Extension::class, $output1);
        $this->assertEquals('extensionName1', $output1->getExtensionName());
        $this->assertEquals('repositoryName1', $output1->getRepositoryName());
        $this->assertEquals('develop', $output1->getBranchName());

        $output2 = $this->subject->create('extensionName2', 'customBranch');

        $this->assertInstanceOf(Extension::class, $output2);
        $this->assertEquals('extensionName2', $output2->getExtensionName());
        $this->assertEquals('repositoryName2', $output2->getRepositoryName());
        $this->assertEquals('customBranch', $output2->getBranchName());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Extension 'invalid' not found in map.
     */
    public function testCreateNonMappedExtension()
    {
        $this->subject->create('invalid');
    }
}
