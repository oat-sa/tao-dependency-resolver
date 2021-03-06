<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use ArgumentCountError;
use LogicException;
use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DependencyResolverCommand extends Command
{
    public const NAME = 'oat:dependencies:resolve';

    /** @var ExtensionFactory */
    private $extensionFactory;

    /** @var DependencyResolver */
    private $dependencyResolver;

    /** @var RepositoryMapAccessor $repositoryMapAccessor */
    private $repositoryMapAccessor;

    public function __construct(
        ExtensionFactory $extensionFactory,
        DependencyResolver $dependencyResolver,
        RepositoryMapAccessor $repositoryMapAccessor
    ) {
        parent::__construct(self::NAME);

        $this->extensionFactory = $extensionFactory;
        $this->dependencyResolver = $dependencyResolver;
        $this->repositoryMapAccessor = $repositoryMapAccessor;
    }

    protected function configure()
    {
        $this
            ->addOption(
                'repository-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the repository being resolved.'
            )
            ->addOption(
                'extension-name',
                null,
                InputOption::VALUE_REQUIRED,
                'Name of the extension being resolved.'
            )
            ->addOption(
                'main-branch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Name of the branch of the repository being resolved.',
                Extension::DEFAULT_BRANCH
            )
            ->addOption(
                'dependency-branches',
                null,
                InputOption::VALUE_REQUIRED,
                'Branch to load for each dependency.',
                ''
            )
            ->addOption(
                'repositories',
                null,
                InputOption::VALUE_NONE,
                'Include repositories information (flag).'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Save composer.json to given file.'
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws NotMappedException when root extension or one of its dependencies is not present in the extension map.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionBranchMap = $this->parseExtensionBranches($input->getOption('dependency-branches'));

        // Builds root extension. Checks that it exists.
        $rootExtension = $this->extensionFactory->create(
            $this->findRootExtensionName($input),
            $input->getOption('main-branch')
        );

        // Resolve all extensions.
        $composerJson = $this->dependencyResolver->resolve(
            $rootExtension,
            $extensionBranchMap,
            $input->getOption('repositories') !== false
        );

        if ($input->getOption('file')) {
            $this->resultToFile($input->getOption('file'), $composerJson);
        }

        // Outputs result.
        $output->writeln($composerJson);
    }

    private function findRootExtensionName(InputInterface $input): string
    {
        $repositoryName = $input->getOption('repository-name');
        $extensionName = $input->getOption('extension-name');

        // We need one and only one amongst repository and extension name.
        if ($repositoryName === null && $extensionName === null
            || $repositoryName !== null && $extensionName !== null
        ) {
            throw new ArgumentCountError('You must provide either a repository name or an extension name to resolve.');
        }

        // Just return extension name
        if ($extensionName !== null) {
            return $extensionName;
        }

        // Finds extension name in the repository list.
        $repositoryMap = $this->repositoryMapAccessor->read();

        if (!isset($repositoryMap[$repositoryName])) {
            throw new NotMappedException(sprintf('Unknown repository "%s".', $repositoryName));
        }
        $repository = $repositoryMap[$repositoryName];
        if (!$repository instanceof Repository || $repository->getExtensionName() === '') {
            throw new NotMappedException(sprintf('Repository "%s" has no extension name.', $repositoryName));
        }

        return $repository->getExtensionName();
    }

    private function parseExtensionBranches(string $extensionBranches): array
    {
        if ($extensionBranches === '') {
            return [];
        }

        $extensionToBranchMap = [];

        foreach (explode(',', $extensionBranches) as $extensionBranch) {
            $extensionBranchParts = explode(':', $extensionBranch);
            if (count($extensionBranchParts) > 2 || $extensionBranchParts[0] === '') {
                throw new LogicException(
                    sprintf('The extensions-branch option has a non-resolvable value: "%s".', $extensionBranch)
                );
            }

            $extensionToBranchMap[$extensionBranchParts[0]] = $extensionBranchParts[1] ?? Extension::DEFAULT_BRANCH;
        }

        return $extensionToBranchMap;
    }

    /**
     * Saves the generated composer.json into a file represented by its path.
     *
     * @param string $file
     * @param string $composerJson
     */
    protected function resultToFile(string $file, string $composerJson): void
    {
        $dir = dirname($file);

        if (file_exists($file)) {
            throw new RuntimeException('Could not save the result, the file already exists.');
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Could not save the result, unable to create the required folder hierarchy.');
        }

        if (false === file_put_contents($file, $composerJson)) {
            throw new RuntimeException('Could not save the result, unable to write the file.');
        }
    }
}
