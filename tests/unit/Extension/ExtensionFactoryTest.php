<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use PHPUnit\Framework\TestCase;

class ExtensionFactoryTest extends TestCase
{
    /** @var ExtensionFactory */
    private $subject;

    const EXTENSION_NAME = 'extension name';
    const REPOSITORY_NAME = 'repository-name';
    const COMPOSER_NAME = 'oat-sa/composer-name';

    public function setUp()
    {
        $extensionMap = [
            self::EXTENSION_NAME => [
                'repository_name' => self::REPOSITORY_NAME,
                'composer_name' => self::COMPOSER_NAME,
            ],
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
        $this->assertEquals(self::COMPOSER_NAME, $extension->getComposerName());
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
        $this->assertEquals(self::COMPOSER_NAME, $extension->getComposerName());
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

    /**
     * @throws NotMappedException
     */
    public function testCreateWithNotMappedRepositoryThrowsException()
    {
        $owner = 'oat-sa';
        $invalidRepositoryName = 'invalid-repository-name';

        $this->expectException(NotMappedException::class);
        $this->expectExceptionMessage('Repository "' . $owner . '/' . $invalidRepositoryName . '" not found in map');
        $this->subject->create($owner . '/' . $invalidRepositoryName);
    }

    /**
     * @throws NotMappedException
     */
    public function testCreateWithMappedRepositoryReturnsExtension()
    {
        $extension = $this->subject->create(self::REPOSITORY_NAME);

        $this->assertInstanceOf(Extension::class, $extension);
        $this->assertEquals(self::EXTENSION_NAME, $extension->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $extension->getRepositoryName());
        $this->assertEquals(self::COMPOSER_NAME, $extension->getComposerName());
        $this->assertEquals(Extension::DEFAULT_BRANCH, $extension->getBranchName());
    }
}
