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
    /** @var RepositoryMapConverter */
    private $repositoryMapConverter;

    public function __construct(RepositoryMapConverter $repositoryMapConverter)
    {
        parent::__construct();

        $this->repositoryMapConverter = $repositoryMapConverter;
    }

    protected function configure()
    {
        $this->setName('repositories:dump')
            ->addOption(
                'filename',
                'f',
                InputArgument::REQUIRED,
                'Filename to which to export the repository table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $this->repositoryMapConverter->toCsv();

        $filename = $input->getOption('filename');

        // Creates directory if necessary.
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($filename, implode("\n", $csv));
    }
}
