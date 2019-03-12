<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use Composer\IO\ConsoleIO;
use OAT\DependencyResolver\Installer\ExtensionInstaller;
use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DependencyResolverCommand extends Command
{
    /** @var ExtensionFactory */
    private $extensionFactory;

    /** @var ExtensionInstaller */
    private $extensionInstaller;

    /** @var DependencyResolver */
    private $dependencyResolver;

    public function __construct(
        ExtensionFactory $extensionFactory,
        ExtensionInstaller $extensionInstaller,
        DependencyResolver $dependencyResolver
    )
    {
        parent::__construct();

        $this->extensionFactory = $extensionFactory;
        $this->extensionInstaller = $extensionInstaller;
        $this->dependencyResolver = $dependencyResolver;
    }

    protected function configure()
    {
        $this
            ->setName('dependencies:resolve')
            ->addArgument('package-name', InputArgument::REQUIRED, 'Name of the extension being tested.')
            ->addOption('package-branch', 'b', InputOption::VALUE_REQUIRED, 'Name of the branch being tested.', Extension::DEFAULT_BRANCH)
            ->addOption('extensions-branch', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Branch to load for each extension.')
            ->addOption('directory', 'd', InputOption::VALUE_REQUIRED, 'Directory in which to download dependencies', __DIR__ . '/../../tmp');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int status code
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Build root extension.
        $rootExtension = $this->extensionFactory->create(
            $input->getArgument('package-name'),
            $input->getOption('package-branch')
        );

        $extensionBranchMap = $this->getExtensionToBranchMap($input->getOption('extensions-branch'));

        // Resolve all extensions.
        $extensionCollection = $this->dependencyResolver->resolve($rootExtension, $extensionBranchMap);

        echo 'The following rxtensions will be installed:', "\n";
        foreach ($extensionCollection as $extension) {
            echo '- ', $extension->getExtensionName(), "\n";
        }

        // Get composer IO Helper
        $consoleIo = new ConsoleIO($input, $output, $this->getHelperSet());
        return $this->extensionInstaller->install($rootExtension, $extensionCollection, $input->getOption('directory'), $consoleIo);
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
