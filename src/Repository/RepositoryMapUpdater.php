<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;
use Packagist\Api\Client as PackagistReader;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class RepositoryMapUpdater implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    /** @var RepositoryReaderInterface */
    private $repositoryReader;

    /** @var PackagistReader */
    private $packagistReader;

    /**
     * RepositoryMapUpdater constructor.
     *
     * @param RepositoryMapAccessor     $repositoryMapAccessor
     * @param RepositoryReaderInterface $repositoryReader
     * @param PackagistReader           $packagistReader
     */
    public function __construct(
        RepositoryMapAccessor $repositoryMapAccessor,
        RepositoryReaderInterface $repositoryReader,
        PackagistReader $packagistReader
    ) {
        $this->repositoryMapAccessor = $repositoryMapAccessor;
        $this->repositoryReader = $repositoryReader;
        $this->packagistReader = $packagistReader;
    }

    /**
     * Reads the repositories name of each repository in the list
     *
     * @param string $userName   Owner of the repositories
     * @param bool   $reloadList Do we need to reload list of repositories?
     * @param int    $limit      Number of repositories names to read
     */
    public function update(string $userName, bool $reloadList, int $limit)
    {
        // Number of updated repositories.
        $updated = 0;
        // Number of skipped repositories.
        $skipped = 0;

        // Reload repositoryList to get repositories not mapped yet.
        if ($reloadList) {
            $this->logger->info($this->reloadList($userName) . ' repositories added.');
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
        $this->logger->info(
            $toUpdate . ' repositories(s) to analyze' . ($limit ? ' (limited to ' . $limit . ')' : '') . '.'
        );

        // Finds all repository that have an empty extension name.
        foreach ($repositoryList as $repositoryName => &$repository) {
            /** @var Repository $repository */
            if ($repository->getExtensionName() === '') {
                $this->logger->info('Analyzing repository "' . $repositoryName . '"...');
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
            $this->logger->info($updated . ' repositories updated.');
        }
        if ($skipped) {
            $this->logger->info($skipped . ' repositories skipped.');
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
        $organizationProperties = $this->repositoryReader->getOrganizationProperties($owner);
        $message = 'Connected to GitHub with http token.' . "\n"
            . 'Organisation "' . $owner . '" has:' . "\n"
            . '- ' . $organizationProperties['public_repos'] . ' public repositories' . "\n"
            . '- ' . $organizationProperties['total_private_repos'] . ' private repositories' . "\n";
        echo $message;

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
     *
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
