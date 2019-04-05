<?php

declare(strict_types=1);

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

    public const COMPOSER_FILENAME = 'composer.json';
    public const MANIFEST_FILENAME = 'manifest.php';

    /** @var GithubConnection */
    public $connectedGithubClient;

    /** @var Parser */
    private $parser;

    public function __construct(GithubConnection $connectedGithubClient, Parser $parser)
    {
        $this->connectedGithubClient = $connectedGithubClient;
        $this->parser = $parser;
    }

    public function getOrganizationProperties(string $owner): array
    {
        return $this->connectedGithubClient->getOrganizationProperties($owner);
    }

    public function getRepositoryList(string $owner): array
    {
        return $this->connectedGithubClient->getRepositoryList($owner);
    }

    public function readRepository(Repository $repository)
    {
        // Adds branches 'develop' and 'master' when they exist.
        foreach (['develop', 'master'] as $branchName) {
            if ($this->branchExists($repository, $branchName)) {
                $repository->addBranch($this->readBranch($repository, $branchName));
            }
        }

        // Finally determines the extension name and composer name.
        $repository->setExtensionName($this->getExtensionName($repository));
        $repository->setComposerName($this->getComposerName($repository));
        $repository->setAnalyzed(true);

        return $repository;
    }

    /**
     * Checks existence of branch in repository.
     *
     * @throws RuntimeException when another error occurs.
     */
    public function branchExists(Repository $repository, string $branchName): bool
    {
        try {
            $this->connectedGithubClient->getBranchReference(
                $repository->getOwner(),
                $repository->getName(),
                $branchName
            );
        } catch (BranchNotFoundException|EmptyRepositoryException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Builds a branch with files.
     */
    public function readBranch(Repository $repository, string $branchName): ?RepositoryBranch
    {
        $this->logger->info('Analyzing branch "' . $branchName . '"...');
        $branch = new RepositoryBranch($branchName);

        // Analyzes manifest and composer.json.
        $file = $this->readManifest($repository, $branchName);
        if ($file !== null) {
            $branch->addFile($file);
        }
        $file = $this->readComposer($repository, $branchName);
        if ($file !== null) {
            $branch->addFile($file);
        }

        return $branch;
    }

    /**
     * Extracts extension name and dependencies from manifest.
     * Returns null if manifest file cannot be found.
     */
    public function readManifest(Repository $repository, string $branchName): ?RepositoryFile
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

    // Extracts extension name from composer.json.
    // Returns null if composer.json cannot be found.
    public function readComposer(Repository $repository, string $branchName): ?RepositoryFile
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
            if (strpos($requirement, $userName . '/') === 0) {
                $requires[] = $requirement;
            }
        }

        return new RepositoryFile(self::COMPOSER_FILENAME, $composerName, $extensionName, $requires);
    }

    public function getManifestContents(string $owner, string $repositoryName, string $branchName): ?string
    {
        return $this->getFileContents($owner, $repositoryName, $branchName, self::MANIFEST_FILENAME);
    }

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

    public function getFileContents(
        string $owner,
        string $repositoryName,
        string $branchName,
        string $filename
    ): ?string {
        $this->logger->info('Retrieving ' . $owner . '/' . $repositoryName . '/' . $branchName . '/' . $filename);

        return $this->connectedGithubClient->getContents($owner, $repositoryName, $branchName, $filename);
    }

    /*
    Extension name can be located either in:
    - manifest.php['name']
    - composer.json['extra']['tao-extension-name']
    Manifest overrides composer if both are present and different.
    */
    public function getExtensionName(Repository $repository): string
    {
        // Tries to find extension from manifest, then composer.json for each branch, until we find.
        foreach ($repository->getBranches() as $branch) {
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

        return '';
    }

    // Composer name can be located in composer.json['name'].
    public function getComposerName(Repository $repository): string
    {
        // Tries to find extension from composer.json for each branch, until we find.
        foreach ($repository->getBranches() as $branch) {
            $file = $branch->getFile(self::COMPOSER_FILENAME);
            if ($file !== null) {
                $extensionName = $file->getComposerName();
                if ($extensionName !== '') {
                    return $extensionName;
                }
            }
        }

        return '';
    }
}
