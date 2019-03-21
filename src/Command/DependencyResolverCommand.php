<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\FileSystem\FileAccessor;
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

    /** @var DependencyResolver */
    private $dependencyResolver;

    /** @var FileAccessor */
    private $fileAccessor;

    /**
     * DependencyResolverCommand constructor.
     *
     * @param ExtensionFactory   $extensionFactory
     * @param DependencyResolver $dependencyResolver
     * @param FileAccessor       $fileAccessor
     */
    public function __construct(
        ExtensionFactory $extensionFactory,
        DependencyResolver $dependencyResolver,
        FileAccessor $fileAccessor
    ) {
        parent::__construct();

        $this->extensionFactory = $extensionFactory;
        $this->dependencyResolver = $dependencyResolver;
        $this->fileAccessor = $fileAccessor;
    }

    protected function configure()
    {
        $this
            ->setName('dependencies:resolve')
            ->addArgument('package-name', InputArgument::REQUIRED, 'Name of the extension or repository being tested.')
            ->addOption(
                'package-branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Name of the branch being tested.',
                Extension::DEFAULT_BRANCH
            )
            ->addOption(
                'extensions-branch',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Branch to load for each extension.'
            )
            ->addOption(
                'directory',
                'd',
                InputOption::VALUE_REQUIRED,
                'Directory in which to download dependencies',
                __DIR__ . '/../../tmp'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int status code
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Builds root extension.
        $rootExtension = $this->extensionFactory->create(
            $input->getArgument('package-name'),
            $input->getOption('package-branch')
        );

        echo 'Resolving dependencies for repository "' . $rootExtension->getRepositoryName() . '"', "\n";

        $extensionBranchMap = $this->getExtensionToBranchMap($input->getOption('extensions-branch'));

        // Resolve all extensions.
        $extensionCollection = $this->dependencyResolver->resolve($rootExtension, $extensionBranchMap);

        echo 'The following extensions will be installed:', "\n";
        foreach ($extensionCollection as $extension) {
            echo '- ', $extension->getExtensionName(), "\n";
        }

        // Generates and write composer.json
        $composerJson = $extensionCollection->generateComposerJson();
        $composerJsonPath = $input->getOption('directory') . DIRECTORY_SEPARATOR . 'composer.json';
        $written = $this->fileAccessor->setContents($composerJsonPath, $composerJson);

        if ($written) {
            echo 'Dumped composer require to "' . realpath($composerJsonPath) . '".', "\n";
        }

        return $written ? 0 : 1;
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
