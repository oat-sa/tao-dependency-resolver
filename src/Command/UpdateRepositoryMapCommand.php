<?php declare(strict_types=1);

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

    /**
     * UpdateRepositoryMapCommand constructor.
     *
     * @param RepositoryMapUpdater $repositoryMapUpdater
     */
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

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->repositoryMapUpdater->update(
            'oat-sa',
            $input->getOption('reload-list'),
            (int)$input->getOption('limit')
        );

        return 1;
    }
}
