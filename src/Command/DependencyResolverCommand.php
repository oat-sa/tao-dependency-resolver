<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
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

    /** @var DependencyResolver */
    private $dependencyResolver;

    public function __construct(
        ExtensionFactory $extensionFactory,
        DependencyResolver $dependencyResolver
    ) {
        parent::__construct();

        $this->extensionFactory = $extensionFactory;
        $this->dependencyResolver = $dependencyResolver;
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
                InputOption::VALUE_REQUIRED,
                'Branch to load for each extension.'
            )
            ->addOption(
                'dump-directory',
                'd',
                InputOption::VALUE_REQUIRED,
                'Directory in which to download dependencies',
                sys_get_temp_dir()
            );
    }

    /**
     * @throws NotMappedException when root extension or one of its dependencies is not present in the extension map.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionBranchMap = $this->parseExtensionBranches($input->getOption('extensions-branch'));

        // Builds root extension. Checks that it exists.
        $rootExtension = $this->extensionFactory->create(
            $input->getArgument('package-name'),
            $input->getOption('package-branch')
        );
        $output->writeln('Resolving dependencies for repository "' . $rootExtension->getRepositoryName() . '".');

        // Resolve all extensions.
        $composerJson = $this->dependencyResolver->resolve($rootExtension, $extensionBranchMap);

        // Outputs result.
        $this->outputResult($composerJson, $input->getOption('dump-directory'), $output);
    }

    private function parseExtensionBranches(string $extensionBranches)
    {
        $extensionToBranchMap = [];

        foreach (explode(',', $extensionBranches) as $extensionBranch) {
            $extensionBranchParts = explode(':', $extensionBranch);
            if (count($extensionBranchParts) > 2 || $extensionBranchParts[0] === '') {
                throw new \LogicException(
                    sprintf('The extensions-branch option has a non-resolvable value: "%s".', $extensionBranch)
                );
            }

            $extensionToBranchMap[$extensionBranchParts[0]] = $extensionBranchParts[1] ?? Extension::DEFAULT_BRANCH;
        }

        return $extensionToBranchMap;
    }

    private function outputResult(string $composerJson, ?string $dumpDirectory, OutputInterface $output)
    {
        // Displays result when no directory is provided.
        if ($dumpDirectory === null) {
            $output->write($composerJson);
            return;
        }

        // Creates directory if necessary.
        if (!is_dir($dumpDirectory)) {
            mkdir($dumpDirectory, 0775, true);
        }

        // Writes json to file.
        $composerJsonPath = rtrim($dumpDirectory, '/\\') . DIRECTORY_SEPARATOR . 'composer.json';
        file_put_contents($composerJsonPath, $composerJson);
        $output->writeln('Dumped composer require to "' . realpath($composerJsonPath) . '".');
    }
}
