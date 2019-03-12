<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Repository\Repository;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;
use OAT\DependencyResolver\Repository\RepositoryReaderInterface;
use Packagist\Api\Client as PackagistReader;

class ExtensionMapUpdater
{
    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    /** @var RepositoryReaderInterface */
    private $repositoryReader;

    /** @var PackagistReader */
    private $packagistReader;

    /**
     * ExtensionMapUpdater constructor.
     * @param RepositoryMapAccessor $repositoryMapAccessor
     * @param RepositoryReaderInterface $repositoryReader
     * @param PackagistReader $packagistReader
     */
    public function __construct(
        RepositoryMapAccessor $repositoryMapAccessor,
        RepositoryReaderInterface $repositoryReader,
        PackagistReader $packagistReader
    )
    {
        $this->repositoryMapAccessor = $repositoryMapAccessor;
        $this->repositoryReader = $repositoryReader;
        $this->packagistReader = $packagistReader;
    }

    /**
     * Reads the extension name of each repository in the list
     * @param string $userName Owner of the repositories
     * @param string $branchName Branch name to refer to when reading repositories
     * @param bool $reloadList Do we need to reload list of extension?
     * @param int $limit Number of extension names to read
     */
    public function updateExtensionNames(string $userName, string $branchName, bool $reloadList, int $limit)
    {
        $this->repositoryReader->output = $this->output;

        // Number of updated extensions.
        $updated = 0;
        // Number of skipped extensions.
        $skipped = 0;

        // Reload repositoryList to get repositories not mapped yet.
        if ($reloadList) {
            $this->output->writeln($this->reloadList($userName) . ' repositories added.');
        }

        // Reads either local or distant repository list.
        $repositoryList = $this->repositoryMapAccessor->read();

        // Displays the number of repositories to update.
        $toUpdate = 0;
        foreach ($repositoryList as $repository) {
            /** @var Repository $repository */
            if ($repository->getExtensionName() === '') {
                $toUpdate++;
            }
        }
        $this->output->writeln($toUpdate . ' repositories(s) to analyze' . ($limit ? ' (limited to ' . $limit . ')' : '') . '.');

        // Finds all repository that have an integer as extension name. All the other ones are supposed to be already ok.
        foreach ($repositoryList as $repositoryName => &$repository) {
            /** @var Repository $repository */
            if ($repository->getExtensionName() === '') {
                $this->output->writeln('Analyzing repository "' . $repositoryName . '"...');
                $repository = $this->repositoryReader->analyzeRepository($repository);

                if (--$limit === 0) {
                    break;
                }
            }
        }

        // Persists updated repositoryList.
        $this->repositoryMapAccessor->write($repositoryList);

        // Displays results.
        if ($updated) {
            $this->output->writeln($updated . ' extension(s) updated.');
        }
        if ($skipped) {
            $this->output->writeln($skipped . ' extension(s) skipped.');
        }
    }

    /**
     * Reads list of repositories from GitHub.
     *
     * @param string $owner
     *
     * @return int Number of repositories added.
     */
    public function reloadList(string $owner)
    {
        // Finds repositories on Github.
        $newReadList = $this->repositoryReader->getRepositoryList($owner);

        // Merges with already mapped repositories.
        $existingMap = $this->repositoryMapAccessor->read();
        $repositoryList = array_merge($newReadList, $existingMap);

        // Finds repositories on Packagist.
        $packagistList = $this->packagistReader->all(['vendor' => $owner]);
        $this->addPackagistPresence($repositoryList, $packagistList);

        // Persists repositoryList first so that we don't need to do this step again.
        $this->repositoryMapAccessor->write($repositoryList);

        // Returns the number of added repositories.
        return count($newReadList) - count($existingMap);
    }

    /**
     * Adds presence on packagist for all repositories found.
     * @param array $repositoryList
     * @param array $packagistList
     */
    public function addPackagistPresence(array $repositoryList, array $packagistList)
    {
        foreach ($packagistList as &$repositoryName) {
            if (isset($repositoryList[$repositoryName])) {
                /** @var Repository $repository */
                $repository = $repositoryList[$repositoryName];
                $repository->setOnPackagist(true);
            }
        }
    }
}
