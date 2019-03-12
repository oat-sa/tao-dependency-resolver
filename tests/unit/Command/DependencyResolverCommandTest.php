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
            'package-name' => new InputArgument('package-name', InputArgument::REQUIRED, 'Name of the extension being tested.'),
        ];
        $this->assertEquals($arguments, $this->subject->getDefinition()->getArguments());
        $options = [
            'package-branch' => new InputOption('package-branch', 'b', InputOption::VALUE_REQUIRED, 'Name of the branch being tested.', Extension::DEFAULT_BRANCH),
            'extensions-branch' => new InputOption('extensions-branch', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Branch to load for each extension.'),
            'directory' => new InputOption('directory', 'd', InputOption::VALUE_REQUIRED, 'Directory in which to download dependencies', realpath(__DIR__ . '/../../../src/Command') . '/../../tmp'),
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
