<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Repository\RepositoryMapConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpRepositoriesCommand extends Command
{
    public const NAME = 'oat:repositories:dump';

    /** @var RepositoryMapConverter */
    private $repositoryMapConverter;

    public function __construct(RepositoryMapConverter $repositoryMapConverter)
    {
        parent::__construct(self::NAME);

        $this->repositoryMapConverter = $repositoryMapConverter;
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'File path and name to which to export the repository table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $this->repositoryMapConverter->toCsv();

        $filePath = $input->getArgument('filepath');

        // Creates directory if necessary.
        $directory = dirname($filePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
        }

        file_put_contents($filePath, implode("\n", $csv));
    }
}
