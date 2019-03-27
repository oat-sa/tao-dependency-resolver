<?php

namespace OAT\DependencyResolver\Repository\Interfaces;

use Github\Exception\ExceptionInterface;
use OAT\DependencyResolver\Repository\Entity\Repository;

interface RepositoryReaderInterface
{
    /**
     * Gets numbers of public and private repositories of a user.
     */
    public function getOrganizationProperties(string $owner): array;

    /**
     * Reads all repositories of a user.
     */
    public function getRepositoryList(string $owner): array;

    /**
     * Analyzes a repository.
     */
    public function analyzeRepository(Repository $repository);

    /**
     * Reads the extension name of a repository.
     */
    public function getExtensionName(Repository $repository, string $branchName): ?string;

    /**
     * Returns contents of manifest.php file for the given repository.
     *
     * @throws \LogicException when the manifest.php is not valid php
     */
    public function getManifestContents(string $owner, string $repositoryName, string $branchName): ?string;

    /**
     * Returns contents of composer.json file for the given repository.
     *
     * @throws \LogicException when the composer.json is not valid json
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
