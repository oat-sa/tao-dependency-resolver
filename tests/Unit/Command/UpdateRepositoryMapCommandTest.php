<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Command;

use OAT\DependencyResolver\Command\UpdateRepositoryMapCommand;
use OAT\DependencyResolver\Repository\RepositoryMapUpdater;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
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

    /** @var RepositoryMapUpdater */
    private $repositoryMapUpdater;

    public function setUp()
    {
        $this->repositoryMapUpdater = $this->createMock(RepositoryMapUpdater::class);

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
