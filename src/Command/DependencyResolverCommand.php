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
use OAT\DependencyResolver\Downloader\RootPackageDownloader;
use OAT\DependencyResolver\Extractor\RemoteManifestDependenciesExtractor;
use OAT\DependencyResolver\Resolver\DependencyResolverInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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

    /** @var RemoteManifestDependenciesExtractor */
    private $remoteManifestDependenciesExtractor;

    public function __construct(
        Factory $factory,
        RootPackageDownloader $rootPackageDownloader,
        DependencyResolverInterface $dependencyResolver,
    RemoteManifestDependenciesExtractor $remoteManifestDependenciesExtractor
    )
    {
        parent::__construct('dependency:resolve');
        $this->rootPackageDownloader = $rootPackageDownloader;
        $this->dependencyResolver = $dependencyResolver;
        $this->factory = $factory;
        $this->remoteManifestDependenciesExtractor = $remoteManifestDependenciesExtractor;
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addArgument('packageName', InputArgument::REQUIRED)
            ->addArgument('directoryName', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        var_dump($this->remoteManifestDependenciesExtractor->extractDependencies('https://raw.githubusercontent.com/oat-sa/tao-core/master/manifest.php'));
        exit;
        $io = new ConsoleIO($input, $output, $this->getHelperSet());
        $directory = $input->getArgument('directoryName');
        $package = $input->getArgument('packageName');

        $config = Factory::createConfig($io);

        $this->rootPackageDownloader->download($config, $package, $directory);

        $composer = $this->factory->createComposer($io, $directory . DIRECTORY_SEPARATOR . 'composer.json', false, $directory);

        $this->install($composer, $io);

        return 1;
    }

    private function install(Composer $composer, IOInterface $io)
    {
        $composer->getEventDispatcher()->addListener(InstallerEvents::PRE_DEPENDENCIES_SOLVING, function(InstallerEvent $event) {
            //$event->getRequest()->install('oat-sa/extension-tao-task-queue');
        });

        $install = Installer::create($io, $composer);

        $install->run();
    }
}
