<?php

namespace OAT\DependencyResolver\Repository;

use Github\Exception\RuntimeException;
use OAT\DependencyResolver\Manifest\Parser;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;
use OAT\DependencyResolver\Repository\Exception\BranchNotFoundException;
use OAT\DependencyResolver\Repository\Exception\EmptyRepositoryException;
use OAT\DependencyResolver\Repository\Exception\FileNotFoundException;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class GitHubRepositoryReader implements RepositoryReaderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    const COMPOSER_FILENAME = 'composer.json';
    const MANIFEST_FILENAME = 'manifest.php';

    /** @var ConnectedGithubClient */
    public $connectedGithubClient;

    /** @var Parser */
    private $parser;

    /**
     * GitHubRepositoryReader constructor.
     *
     * @param ConnectedGithubClient $connectedGithubClient
     * @param Parser                $parser
     */
    public function __construct(ConnectedGithubClient $connectedGithubClient, Parser $parser)
    {
        $this->connectedGithubClient = $connectedGithubClient;
        $this->parser = $parser;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganizationProperties(string $owner): array
    {
        return $this->connectedGithubClient->getOrganizationProperties($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryList(string $owner): array
    {
        return $this->connectedGithubClient->getRepositoryList($owner);
    }

    /**
     * {@inheritdoc}
     */
    public function analyzeRepository(Repository $repository)
    {
        // Check existence of branches 'develop', 'master', or derivated (e.g. development instead of develop).
        $branches = [];
        foreach (['develop', 'master'] as $branchName) {
            // Checks for branch existence.
            try {
                $branches = array_merge($branches, $this->findBranch($repository, $branchName));
            } catch (EmptyRepositoryException $exception) {
                break;
            }
        }

        // Analyzes existing branches.
        foreach (array_keys($branches) as $branchName) {
            $repository->addBranch($this->analyzeBranch($repository, $branchName));
        }

        // Finally determines the extension name and composer name.
        $repository->setExtensionName($this->getExtensionName($repository, 'develop'));
        $repository->setComposerName($this->getComposerName($repository, 'develop'));

        return $repository;
    }

    /**
     * Checks existence of branch in repository.
     *
     * @param Repository $repository
     * @param string     $branchName
     *
     * @return array
     * @throws EmptyRepositoryException when the repository is empty.
     * @throws RuntimeException when another error occurs.
     */
    public function findBranch(Repository $repository, string $branchName): array
    {
        try {
            $branchRef = $this->connectedGithubClient->getBranchReference(
                $repository->getOwner(),
                $repository->getName(),
                $branchName
            );
        } catch (BranchNotFoundException $exception) {
            return [];
        }

        return [$branchName => $branchRef];
    }

    /**
     * Builds a branch with files.
     *
     * @param Repository $repository
     * @param string     $branchName
     *
     * @return RepositoryBranch|null
     */
    public function analyzeBranch(Repository $repository, string $branchName): ?RepositoryBranch
    {
        $this->logger->info('Analyzing branch "' . $branchName . '"...');
        $branch = new RepositoryBranch($branchName);

        // Analyzes manifest and composer.json.
        $file = $this->analyzeManifest($repository, $branchName);
        if ($file !== null) {
            $branch->addFile($file);
        }
        $file = $this->analyzeComposer($repository, $branchName);
        if ($file !== null) {
            $branch->addFile($file);
        }

        return $branch;
    }

    /**
     * Extracts extension name from manifest.
     * Returns null if manifest file cannot be found.
     *
     * @param Repository $repository
     * @param string     $branchName
     *
     * @return RepositoryFile|null
     */
    public function analyzeManifest(Repository $repository, string $branchName): ?RepositoryFile
    {
        try {
            $manifestContents = $this->getManifestContents(
                $repository->getOwner(),
                $repository->getName(),
                $branchName
            );
        } catch (FileNotFoundException $exception) {
            return null;
        }

        $extensionName = $this->parser->getExtensionName($manifestContents);
        $requires = $this->parser->getDependencyNames($manifestContents);

        return new RepositoryFile(self::MANIFEST_FILENAME, '', $extensionName, $requires);
    }

    /**
     * Extracts extension name from composer.json.
     * Returns null if composer.json cannot be found.
     *
     * @param Repository $repository
     * @param string     $branchName
     *
     * @return RepositoryFile|null
     */
    public function analyzeComposer(Repository $repository, string $branchName): ?RepositoryFile
    {
        try {
            $composerContents = $this->getComposerContents(
                $repository->getOwner(),
                $repository->getName(),
                $branchName
            );
        } catch (FileNotFoundException $exception) {
            return null;
        } catch (\LogicException $exception) {
            return new RepositoryFile(self::COMPOSER_FILENAME, '', '', []);
        }

        $composerName = $composerContents['name'] ?? '';
        $extensionName = $composerContents['extra']['tao-extension-name'] ?? '';

        // Extracts requires from composer.
        $userName = $repository->getOwner();
        $requires = [];
        $requirements = $composerContents['require'] ?? [];
        foreach (array_keys($requirements) as $requirement) {
            if (substr($requirement, 0, strlen($userName) + 1) === $userName . '/') {
                $requires[] = $requirement;
            }
        }

        return new RepositoryFile(self::COMPOSER_FILENAME, $composerName, $extensionName, $requires);
    }

    /**
     * {@inheritdoc}
     */
    public function getManifestContents(string $owner, string $repositoryName, string $branchName): ?string
    {
        return $this->getFileContents($owner, $repositoryName, $branchName, self::MANIFEST_FILENAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getComposerContents(string $owner, string $repositoryName, string $branchName): array
    {
        $json = $this->getFileContents($owner, $repositoryName, $branchName, self::COMPOSER_FILENAME);

        $array = json_decode($json, true);
        if ($array === null) {
            throw new \LogicException(
                sprintf(
                    'File "%s" in branch "%s" of repository "%s/%s" is not valid json.',
                    self::COMPOSER_FILENAME,
                    $branchName,
                    $owner,
                    $repositoryName
                )
            );
        }

        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileContents(
        string $owner,
        string $repositoryName,
        string $branchName,
        string $filename
    ): ?string {
        $this->logger->info('Retrieving ' . $owner . '/' . $repositoryName . '/' . $branchName . '/' . $filename);

        return $this->connectedGithubClient->getContents($owner, $repositoryName, $branchName, $filename);
    }

    /**
     * {@inheritdoc}
     * Extension name can be located either in:
     * - manifest.php['name']
     * - composer.json['extra']['tao-extension-name']
     * Manifest overrides composer if both are present and different.
     */
    public function getExtensionName(Repository $repository, string $branchName): ?string
    {
        // Tries to get extension name from given branch and fallback to develop, master or any other branch existing.
        $branchNames = [$branchName];
        if ($branchName !== 'develop') {
            $branchNames[] = 'develop';
        }
        if ($branchName !== 'master') {
            $branchNames[] = 'master';
        }
        foreach (array_keys($repository->getBranches()) as $existingBranchName) {
            if (! in_array($existingBranchName, $branchNames)) {
                $branchNames[] = $existingBranchName;
            }
        }

        // Tries to find extension from manifest, then composer.json for each branch, until we find.
        foreach ($branchNames as $branchName) {
            $branch = $repository->getBranch($branchName);

            if ($branch !== null) {
                foreach ([self::MANIFEST_FILENAME, self::COMPOSER_FILENAME] as $filename) {
                    $file = $branch->getFile($filename);
                    if ($file !== null) {
                        $extensionName = $file->getExtensionName();
                        if ($extensionName !== '') {
                            return $extensionName;
                        }
                    }
                }
            }
        }

        return '__NOT_FOUND__';
    }

    /**
     * {@inheritdoc}
     * Composer name can be located in composer.json['name'].
     */
    public function getComposerName(Repository $repository, string $branchName): ?string
    {
        // Tries to get extension name from given branch and fallback to develop, master or any other branch existing.
        $branchNames = [$branchName];
        if ($branchName !== 'develop') {
            $branchNames[] = 'develop';
        }
        if ($branchName !== 'master') {
            $branchNames[] = 'master';
        }
        foreach (array_keys($repository->getBranches()) as $existingBranchName) {
            if (! in_array($existingBranchName, $branchNames)) {
                $branchNames[] = $existingBranchName;
            }
        }

        // Tries to find extension from composer.json for each branch, until we find.
        foreach ($branchNames as $branchName) {
            $branch = $repository->getBranch($branchName);

            if ($branch !== null) {
                $file = $branch->getFile(self::COMPOSER_FILENAME);
                if ($file !== null) {
                    $extensionName = $file->getComposerName();
                    if ($extensionName !== '') {
                        return $extensionName;
                    }
                }
            }
        }

        return '__NOT_FOUND__';
    }
}
