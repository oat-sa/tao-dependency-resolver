<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository\Interfaces;

use Github\Exception\ExceptionInterface;
use OAT\DependencyResolver\Repository\Entity\Repository;

interface RepositoryReaderInterface
{
    /**
     * Gets numbers of public and private repositories of a user.
     * @param string $owner
     * @return array
     */
    public function getOrganizationProperties(string $owner): array;

    /**
     * Reads all repositories of a user.
     * @param string $owner
     * @return array
     */
    public function getRepositoryList(string $owner): array;

    public function getRepository(string $owner, string $repositoryName): Repository;

    /**
     * Builds a representation of a distant code repository.
     * @param Repository $repository
     */
    public function readRepository(Repository $repository);

    /**
     * Reads the extension name of a repository.
     * @param Repository $repository
     * @return string|null
     */
    public function getExtensionName(Repository $repository): ?string;

    /**
     * Returns contents of manifest.php file for the given repository.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     * @return string|null
     */
    public function getManifestContents(string $owner, string $repositoryName, string $branchName): ?string;

    /**
     * Returns contents of composer.json file for the given repository.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     * @return array
     */
    public function getComposerContents(string $owner, string $repositoryName, string $branchName): array;

    /**
     * Returns contents of a file for the given repository.
     *
     * @throws ExceptionInterface null when an error occurred reading the file.
     */
    public function getFileContents(
        string $owner,
        string $repositoryName,
        string $branchName,
        string $filename
    ): ?string;
}
