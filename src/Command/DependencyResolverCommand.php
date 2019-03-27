<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\FileSystem\Exception\FileAccessException;
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
                sys_get_temp_dir()
            );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Builds root extension.
        $rootExtension = $this->extensionFactory->create(
            $input->getArgument('package-name'),
            $input->getOption('package-branch')
        );

        $output->writeln('Resolving dependencies for repository "' . $rootExtension->getRepositoryName() . '".');

        $extensionBranchMap = $this->getExtensionToBranchMap($input->getOption('extensions-branch'));

        // Resolve all extensions.
        $extensionCollection = $this->dependencyResolver->resolve($rootExtension, $extensionBranchMap);

        $output->writeln('The following extensions will be installed:');
        foreach ($extensionCollection as $extension) {
            $output->writeln('- ', $extension->getExtensionName());
        }

        // Generates and write composer.json
        $composerJson = $extensionCollection->generateComposerJson();
        $composerJsonPath = $input->getOption('directory') . DIRECTORY_SEPARATOR . 'composer.json';
        try {
            $this->fileAccessor->setContents($composerJsonPath, $composerJson);
        } catch (FileAccessException $exception) {
            $output->writeln(
                sprintf(
                    'An error occurred while writing composer.json to "%s": %s',
                    realpath($composerJsonPath),
                    $exception->getMessage()
                )
            );

            return 1;
        }

        $output->writeln('Dumped composer require to "' . realpath($composerJsonPath) . '".');
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
