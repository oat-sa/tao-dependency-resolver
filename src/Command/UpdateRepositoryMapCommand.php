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
    public const NAME = 'oat:repositories:update';

    /** @var RepositoryMapUpdater */
    private $repositoryMapUpdater;

    /** @var string */
    private $organizationName;

    public function __construct(RepositoryMapUpdater $repositoryMapUpdater, string $organizationName)
    {
        parent::__construct(self::NAME);

        $this->repositoryMapUpdater = $repositoryMapUpdater;
        $this->organizationName = $organizationName;
    }

    protected function configure()
    {
        $this
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
                'Limits the number of extension names read: 0 means no limit, upper limit is 100.',
                0
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Reload repositoryList to get repositories not mapped yet.
        if ($input->getOption('reload-list')) {
            $output->writeln($this->repositoryMapUpdater->reloadList($this->organizationName) . ' repositories added.');
        }

        $limit = $input->getOption('limit');
        if (!is_numeric($limit)) {
            throw new \LogicException('Limit option must be an integer between 0 (no limit) and 100');
        }

        $limit = min(max(intval($limit), 0), 100);

        $this->repositoryMapUpdater->update($limit);
    }
}
