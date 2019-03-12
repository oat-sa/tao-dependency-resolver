<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Installer\ExtensionInstaller;
use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Unit tests for DependencyResolverCommand class.
 */
class DependencyResolverCommandTest extends TestCase
{
    use ProtectedAccessorTrait;

    /** @var DependencyResolverCommand */
    private $subject;

    /** @var ExtensionFactory */
    private $extensionFactory;

    /** @var ExtensionInstaller */
    private $extensionInstaller;

    /** @var DependencyResolver */
    private $dependencyResolver;

    public function setUp()
    {
        $this->extensionFactory = $this->createMock(ExtensionFactory::class);
        $this->extensionInstaller = $this->createMock(ExtensionInstaller::class);
        $this->dependencyResolver = $this->createMock(DependencyResolver::class);

        $this->subject = new DependencyResolverCommand($this->extensionFactory, $this->extensionInstaller, $this->dependencyResolver);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(DependencyResolverCommand::class, $this->subject);
        $this->assertEquals($this->extensionFactory, $this->getPrivateProperty($this->subject, 'extensionFactory'));
        $this->assertEquals($this->extensionInstaller, $this->getPrivateProperty($this->subject, 'extensionInstaller'));
        $this->assertEquals($this->dependencyResolver, $this->getPrivateProperty($this->subject, 'dependencyResolver'));
    }

    public function testConfigure()
    {
        $this->assertEquals('dependencies:resolve', $this->subject->getName());

        $arguments = [
            'package-remote-url' => new InputArgument('package-remote-url', InputArgument::REQUIRED, 'Name of the extension being tested.'),
            'package-branch' => new InputArgument('package-branch', InputArgument::OPTIONAL, 'Name of the branch being tested.', Extension::DEFAULT_BRANCH),
            'directory' => new InputArgument('directory', InputArgument::OPTIONAL, 'Directory in which to download dependencies', realpath(__DIR__ . '/../../../src/Command') . '/../../tmp'),
        ];
        $this->assertEquals($arguments, $this->subject->getDefinition()->getArguments());
        $options = [
            'dependencies-branch' => new InputOption('dependencies-branch', 'ext', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY),
        ];
        $this->assertEquals($options, $this->subject->getDefinition()->getOptions());
    }

    /*
     * taoProctoring
- funcAcl
- generis
- tao
- taoBackOffice
- taoDelivery
- taoDeliveryRdf
- taoEventLog
- taoGroups
- taoItems
- taoOutcomeUi
- taoQtiItem
- taoQtiTest
- taoResultServer
- taoTests
- taoTestTaker

     */
}
