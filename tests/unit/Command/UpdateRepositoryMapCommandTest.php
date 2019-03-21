<?php

declare(strict_types=1);

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
    use ProtectedAccessorTrait;

    /** @var UpdateRepositoryMapCommand */
    private $subject;

    /** @var ExtensionMapUpdater */
    private $extensionMapUpdater;

    public function setUp()
    {
        $this->extensionMapUpdater = $this->createMock(ExtensionMapUpdater::class);
        $this->extensionMapFactory = $this->createMock(ExtensionMapFactory::class);

        $this->subject = new UpdateRepositoryMapCommand($this->repositoryMapUpdater);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(UpdateRepositoryMapCommand::class, $this->subject);
        $this->assertEquals(
            $this->repositoryMapUpdater,
            $this->getPrivateProperty($this->subject, 'repositoryMapUpdater')
        );
    }

    public function testConfigure()
    {
        $this->assertEquals('repositories:update', $this->subject->getName());

        $options = [
            'reload-list' => new InputOption(
                'reload-list',
                'r',
                InputOption::VALUE_NONE,
                'Reloads the list of repositories.'
            ),
            'limit' => new InputOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limits the number of extension names read to pace the API calls.'
            ),
        ];
        $this->assertEquals($options, $this->subject->getDefinition()->getOptions());
    }
}
