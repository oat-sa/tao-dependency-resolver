<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Command;

use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpRepositoriesCommand extends Command
{
    const DEFAULT_CSV_FILE = __DIR__ . '/../../repositories.csv';

    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    /**
     * UpdateRepositoryMapCommand constructor.
     * @param RepositoryMapAccessor $repositoryMapAccessor
     */
    public function __construct(RepositoryMapAccessor $repositoryMapAccessor)
    {
        parent::__construct();

        $this->repositoryMapAccessor = $repositoryMapAccessor;
    }

    protected function configure()
    {
        $this->setName('repositories:dump')
            ->addOption('filename', 'f', InputOption::VALUE_REQUIRED, 'Filename to which to export the repository table', self::DEFAULT_CSV_FILE);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csv = $this->repositoryMapAccessor->exportCsv();

        file_put_contents($input->getOption('filename'), implode("\n", $csv));

        return 1;
    }
}
