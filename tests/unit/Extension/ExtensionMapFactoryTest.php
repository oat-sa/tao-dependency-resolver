<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Repository\Repository;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExtensionMapFactoryTest extends TestCase
{
    use ProtectedAccessorTrait;

    const OWNER_NAME = 'the name of the owner';
    const REPOSITORY_NAME = 'the name of the repository';
    const EXTENSION_NAME = 'the name of the extension as well';

    /** @var ExtensionMapFactory */
    private $subject;

    /** @var RepositoryMapAccessor|MockObject */
    private $extensionMapAccessor;

    public function setUp()
    {
        $repository = $this->createConfiguredMock(Repository::class, ['getOwner' => self::OWNER_NAME, 'getName' => self::REPOSITORY_NAME, 'getExtensionName' => self::EXTENSION_NAME]);
        $this->extensionMapAccessor = $this->createConfiguredMock(RepositoryMapAccessor::class, ['read' => [$repository]]);

        $this->subject = new ExtensionMapFactory($this->extensionMapAccessor);
    }

    public function testConstructor_ReturnsExtensionMapFactoryInstance()
    {
        $this->assertInstanceOf(ExtensionMapFactory::class, $this->subject);
        $this->assertInstanceOf(RepositoryMapAccessor::class, $this->getPrivateProperty($this->subject, 'repositoryMapAccessor'));
    }

    public function testCreate_ReturnsExtensionMapAccessorReadResult()
    {
        $this->assertEquals([self::EXTENSION_NAME => self::OWNER_NAME . '/' . self::REPOSITORY_NAME], $this->subject->create());
    }
}
