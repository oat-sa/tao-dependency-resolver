<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Extension;

use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use PHPUnit\Framework\TestCase;

class ExtensionFactoryTest extends TestCase
{
    private const EXTENSION_NAME = 'extension name';
    private const REPOSITORY_NAME = 'repository-name';

    /** @var ExtensionFactory */
    private $subject;

    public function setUp()
    {
        $extensionMap = [
            self::EXTENSION_NAME => self::REPOSITORY_NAME,
        ];
        $this->subject = new ExtensionFactory($extensionMap);
    }

    /**
     * @throws NotMappedException
     */
    public function testCreateWithMappedExtensionsAndNoBranchNameReturnsExtensionWithDefaultBranch()
    {
        $extension = $this->subject->create(self::EXTENSION_NAME);

        $this->assertInstanceOf(Extension::class, $extension);
        $this->assertEquals(self::EXTENSION_NAME, $extension->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $extension->getRepositoryName());
        $this->assertEquals(Extension::DEFAULT_BRANCH, $extension->getBranchName());
    }

    /**
     * @throws NotMappedException
     */
    public function testCreateWithMappedExtensionsAndBranchNameReturnsExtensionWithBranchName()
    {
        $customBranch = 'customBranch';

        $extension = $this->subject->create(self::EXTENSION_NAME, $customBranch);

        $this->assertInstanceOf(Extension::class, $extension);
        $this->assertEquals(self::EXTENSION_NAME, $extension->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $extension->getRepositoryName());
        $this->assertEquals($customBranch, $extension->getBranchName());
    }

    /**
     * @throws NotMappedException
     */
    public function testCreateWithNotMappedExtensionThrowsException()
    {
        $invalidExtensionName = 'invalid';

        $this->expectException(NotMappedException::class);
        $this->expectExceptionMessage('Extension "' . $invalidExtensionName . '" not found in map');
        $this->subject->create($invalidExtensionName);
    }
}
