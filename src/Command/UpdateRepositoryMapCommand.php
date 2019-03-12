<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use Composer\IO\ConsoleIO;
use OAT\DependencyResolver\Extension\ExtensionMapFactory;
use OAT\DependencyResolver\Extension\ExtensionMapUpdater;
use OAT\DependencyResolver\Manifest\RepositoryReaderInterface;
use OAT\DependencyResolver\Installer\ExtensionInstaller;
use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Resolver\ManifestDependencyResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Github\Client;

class UpdateRepositoryMapCommand extends Command
{
    /** @var ExtensionMapUpdater */
    private $extensionMapUpdater;

    /** @var ExtensionMapFactory */
    private $extensionMapFactory;

    /**
     * UpdateRepositoryMapCommand constructor.
     * @param ExtensionMapUpdater $extensionMapUpdater
     * @param ExtensionMapFactory $extensionMapFactory
     */
    public function __construct(ExtensionMapUpdater $extensionMapUpdater, ExtensionMapFactory $extensionMapFactory)
    {
        parent::__construct();

        $this->extensionMapUpdater = $extensionMapUpdater;
        $this->extensionMapFactory = $extensionMapFactory;
    }

    protected function configure()
    {
        $this->setName('repositories:update')
            ->addOption('branch', 'b', InputOption::VALUE_REQUIRED, 'Branch name to rely on when reading repositories.', Extension::DEFAULT_BRANCH)
            ->addOption('reload-list', 'r', InputOption::VALUE_NONE, 'Reloads the list of repositories.')
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Limits the number of extension names read to pace the API calls.', 0);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->extensionMapUpdater->output = $output;
        $this->extensionMapUpdater->updateExtensionNames(
            'oat-sa',
            $input->getOption('branch'),
            $input->getOption('reload-list'),
            (int)$input->getOption('limit')
        );

        return 1;
    }
}
