<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\EventDispatcher\Event;
use Composer\Factory;
use Composer\Installer;
use Composer\Installer\InstallerEvent;
use Composer\Installer\InstallerEvents;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Composer\Semver\Constraint\Constraint;
use OAT\DependencyResolver\Downloader\RootPackageDownloader;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use OAT\DependencyResolver\Extractor\RemoteExtensionComposerNameExtractor;
use OAT\DependencyResolver\Extractor\RemoteManifestExtensionsExtractor;
use OAT\DependencyResolver\Factory\ExtensionFactory;
use OAT\DependencyResolver\Resolver\DependencyResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DependencyResolverCommand extends Command
{
    /** @var RootPackageDownloader */
    private $rootPackageDownloader;

    /** @var DependencyResolverInterface */
    private $dependencyResolver;

    /** @var Factory */
    private $factory;

    /** @var RemoteManifestExtensionsExtractor */
    private $remoteManifestDependenciesExtractor;

    /** @var RemoteExtensionComposerNameExtractor */
    private $remoteExtensionComposerNameExtractor;

    /** @var ExtensionFactory */
    private $extensionFactory;

    public function __construct(
        Factory $factory,
        RootPackageDownloader $rootPackageDownloader,
        DependencyResolverInterface $dependencyResolver,
        RemoteManifestExtensionsExtractor $remoteManifestDependenciesExtractor,
        RemoteExtensionComposerNameExtractor $remoteExtensionComposerNameExtractor,
        ExtensionFactory $extensionFactory
    )
    {
        parent::__construct('dependency:resolve');

        $this->rootPackageDownloader = $rootPackageDownloader;
        $this->dependencyResolver = $dependencyResolver;
        $this->factory = $factory;
        $this->remoteManifestDependenciesExtractor = $remoteManifestDependenciesExtractor;
        $this->remoteExtensionComposerNameExtractor = $remoteExtensionComposerNameExtractor;
        $this->extensionFactory = $extensionFactory;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('rootExtensionName', InputArgument::REQUIRED)
            ->addArgument('rootExtensionBranch', InputArgument::REQUIRED)
            ->addArgument('directoryName', InputArgument::REQUIRED)
            ->addOption('extensionBranch', 'ext', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $directory = $input->getArgument('directoryName');
        $rootExtensionName = $input->getArgument('rootExtensionName');
        $rootExtensionBranch = $input->getArgument('rootExtensionBranch');
        $extensionToBranchMap = $this->getExtensionToBranchMap($input->getOption('extensionBranch'));

        $rootExtension = $this->extensionFactory->create($rootExtensionName, $rootExtensionBranch);

        $extensionCollection = $this->remoteManifestDependenciesExtractor->extractExtensionsRecursively(
            new ExtensionCollection(),
            $rootExtension,
            $extensionToBranchMap
        );

        $config = Factory::createConfig($io);

        $this->rootPackageDownloader->download(
            $config,
            $this->remoteExtensionComposerNameExtractor->extractComposerName($rootExtension) . ':' . $rootExtension->getPrefixedBranch(),
            $directory
        );

        $composer = $this->factory->createComposer($io, $directory . DIRECTORY_SEPARATOR . 'composer.json', false, $directory);

        $this->install($composer, $extensionCollection, $io);

        return 1;
    }

    private function install(Composer $composer, ExtensionCollection $extensionCollection, IOInterface $io)
    {
        $composer->getEventDispatcher()->addListener(InstallerEvents::PRE_DEPENDENCIES_SOLVING, function (InstallerEvent $event) use ($extensionCollection) {
            foreach ($extensionCollection->all() as $extension) {
                $event->getRequest()->install(
                    $this->remoteExtensionComposerNameExtractor->extractComposerName($extension),
                    new Constraint('==', $extension->getPrefixedBranch())
                );
            }
        });

        $install = Installer::create($io, $composer);

        $install->run();
    }

    private function getExtensionToBranchMap(array $extensionsBranches)
    {
        $extensionToBranchMap = [];

        foreach ($extensionsBranches as $extensionBranch) {
            $extensionBranchParts = explode(':', $extensionBranch);

            $extensionToBranchMap[$extensionBranchParts[0]] = $extensionBranchParts[1];
        }

        return $extensionToBranchMap;
    }
}
