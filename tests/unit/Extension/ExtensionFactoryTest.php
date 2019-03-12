<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use PHPUnit\Framework\TestCase;

class ExtensionFactoryTest extends TestCase
{
    /** @var ExtensionFactory */
    private $subject;

    const EXTENSION_NAME = 'extension name';
    const REPOSITORY_NAME = 'repository name';

    public function setUp()
    {
        $this->subject = new ExtensionFactory([self::EXTENSION_NAME => self::REPOSITORY_NAME]);
    }

    public function testCreate_WithMappedExtensionsAndNoBranchName_ReturnsExtensionWithDefaultBranch()
    {
        $extension = $this->subject->create(self::EXTENSION_NAME);

        $this->assertInstanceOf(Extension::class, $extension);
        $this->assertEquals(self::EXTENSION_NAME, $extension->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $extension->getRepositoryName());
        $this->assertEquals(Extension::DEFAULT_BRANCH, $extension->getBranchName());
    }

    public function testCreate_WithMappedExtensionsAndBranchName_ReturnsExtensionWithBranchName()
    {
        $customBranch = 'customBranch';

        $extension = $this->subject->create(self::EXTENSION_NAME, $customBranch);

        $this->assertInstanceOf(Extension::class, $extension);
        $this->assertEquals(self::EXTENSION_NAME, $extension->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $extension->getRepositoryName());
        $this->assertEquals($customBranch, $extension->getBranchName());
    }

    public function testCreateNonMappedExtension()
    {
        $invalidExtensionName = 'invalid';

        $this->expectException(NotMappedException::class);
        $this->expectExceptionMessage('Extension "' . $invalidExtensionName . '" not found in map');
        $this->subject->create($invalidExtensionName);
    }
}
