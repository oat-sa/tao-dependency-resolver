<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpRepositoriesCommand extends Command
{
    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    public function __construct(RepositoryMapAccessor $repositoryMapAccessor)
    {
        parent::__construct();

        $this->repositoryMapAccessor = $repositoryMapAccessor;
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
        $csv = $this->repositoryMapAccessor->exportCsv();

        file_put_contents($input->getOption('filename'), implode("\n", $csv));
    }
}
