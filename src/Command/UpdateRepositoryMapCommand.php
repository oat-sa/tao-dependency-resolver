<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Repository\RepositoryMapUpdater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateRepositoryMapCommand extends Command
{
    /** @var RepositoryMapUpdater */
    private $repositoryMapUpdater;

    public function __construct(RepositoryMapUpdater $repositoryMapUpdater)
    {
        parent::__construct();

        $this->repositoryMapUpdater = $repositoryMapUpdater;
    }

    protected function configure()
    {
        $this->setName('repositories:update')
            ->addOption(
                'reload-list',
                'r',
                InputOption::VALUE_NONE,
                'Reloads the list of repositories.'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limits the number of extension names read to pace the API calls.',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Reload repositoryList to get repositories not mapped yet.
        if ($input->getOption('reload-list')) {
            $output->writeln($this->repositoryMapUpdater->reloadList('oat-sa') . ' repositories added.');
        }

        $this->repositoryMapUpdater->update((int)$input->getOption('limit'));
    }
}
