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
            ->addArgument('package-remote-url', InputArgument::REQUIRED, 'Name of the extension being tested.')
            ->addArgument('package-branch', InputArgument::OPTIONAL, 'Name of the branch being tested.', Extension::DEFAULT_BRANCH)
            ->addArgument('directory', InputArgument::OPTIONAL, 'Directory in which to download dependencies', __DIR__ . '/../../tmp')
            ->addOption('dependencies-branch', 'ext', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);
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
            $input->getArgument('package-remote-url'),
            $input->getArgument('package-branch')
        );

        $extensionBranchMap = $this->getExtensionToBranchMap($input->getOption('dependencies-branch'));

        // Resolve all extensions.
        $extensionCollection = $this->dependencyResolver->resolve($rootExtension, $extensionBranchMap);

        foreach($extensionCollection as $extension) {
            var_dump($extension->getExtensionName());
        }
        exit;

        // Get composer IO Helper
        $consoleIo = new ConsoleIO($input, $output, $this->getHelperSet());
        return $this->extensionInstaller->install($rootExtension, $extensionCollection, $input->getArgument('directory'), $consoleIo);
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
