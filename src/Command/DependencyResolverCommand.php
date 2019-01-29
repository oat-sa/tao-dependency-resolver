<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use Composer\EventDispatcher\Event;
use Composer\Factory;
use Composer\Installer;
use Composer\Installer\InstallerEvent;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use OAT\DependencyResolver\Downloader\RootPackageDownloader;
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

    /** @var IOInterface */
    private $IO;
    /**
     * @var Factory
     */
    private $factory;

    public function __construct(
        IOInterface $IO,
        Factory $factory,
        RootPackageDownloader $rootPackageDownloader,
        DependencyResolverInterface $dependencyResolver
    )
    {
        parent::__construct('dependency:resolve');

        $this->IO = $IO;
        $this->rootPackageDownloader = $rootPackageDownloader;
        $this->dependencyResolver = $dependencyResolver;
        $this->factory = $factory;
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
        $directory = $input->getArgument('directoryName');
        $package = $input->getArgument('packageName');

        $config = Factory::createConfig();

        $this->rootPackageDownloader->download($config, $package, $directory);

        $io = new ConsoleIO($input, $output, $this->getHelperSet());

        $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);

        $composer = $this->factory->createComposer($io, $directory . DIRECTORY_SEPARATOR . 'composer.json', false, $directory);

        //$composer->getEventDispatcher()->addListener(Installer\InstallerEvents::POST_DEPENDENCIES_SOLVING, function(InstallerEvent $event) {
        //    var_dump($event->getOperations());
        //    //$event->getRequest()->install('oat-sa/extension-tao-task-queue');
        //});

        //$this->dependencyResolver->resolve($composer, 'whatever');

        $install = Installer::create($io, $composer);

        $install->run();

        return 1;
    }
}
