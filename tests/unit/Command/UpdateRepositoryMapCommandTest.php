<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionMapFactory;
use OAT\DependencyResolver\Extension\ExtensionMapUpdater;
use OAT\DependencyResolver\Repository\RepositoryReaderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputOption;

/**
 * Unit tests for UpdateRepositoryMapCommand class.
 */
class UpdateRepositoryMapCommandTest extends TestCase
{
    /** @var DependencyResolverCommand */
    private $subject;

    /** @var ExtensionMapUpdater */
    private $extensionMapUpdater;

    /** @var ExtensionMapFactory */
    private $extensionMapFactory;

    public function setUp()
    {
        $this->extensionMapUpdater = $this->createMock(ExtensionMapUpdater::class);
        $this->extensionMapFactory = $this->createMock(ExtensionMapFactory::class);

        $this->subject = new UpdateRepositoryMapCommand($this->extensionMapUpdater, $this->extensionMapFactory);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(UpdateRepositoryMapCommand::class, $this->subject);
        $this->assertEquals($this->extensionMapUpdater, $this->getPrivateProperty($this->subject, 'extensionMapUpdater'));
        $this->assertEquals($this->extensionMapFactory, $this->getPrivateProperty($this->subject, 'extensionMapFactory'));
    }

    public function testConfigure()
    {
        $this->assertEquals('repositories:update', $this->subject->getName());

        $options = [
            'branch' => new InputOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch name to rely on when reading repositories.', Extension::DEFAULT_BRANCH),
            'reload-list' => new InputOption('reload-list', 'r', InputOption::VALUE_NONE, 'Reloads the list of repositories.'),
            'limit' => new InputOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limits the number of extension names read to pace the API calls.'),
        ];
        $this->assertEquals($options, $this->subject->getDefinition()->getOptions());
    }

    public function getPrivateProperty($object, $property)
    {
        $reflector = new \ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);
        $value = $reflector->getValue($object);
        $reflector->setAccessible(false);
        return $value;
    }
}
