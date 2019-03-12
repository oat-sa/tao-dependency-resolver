<?php
/**
 * Created by PhpStorm.
 * User: julien
 * Date: 28/02/19
 * Time: 12:23
 */

namespace OAT\DependencyResolver\Repository;

use Github\Exception\ExceptionInterface;

interface RepositoryReaderInterface
{
    /**
     * Reads all repositories of a user.
     *
     * @param string $owner
     *
     * @return array
     */
    public function getRepositoryList(string $owner): array;

    /**
     * Analyzes a repository.
     *
     * @param Repository $repository
     */
    public function analyzeRepository(Repository $repository);

    /**
     * Reads the extension name of a repository.
     *
     * @param Repository $repository
     * @param string $branchName
     *
     * @return string|null extension names or null if it can not be found.
     */
    public function getExtensionName(Repository $repository, string $branchName): ?string;

    /**
     * Returns contents of manifest.php file for the given repository.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return string|null
     * @throws \LogicException when the manifest.php is not valid php
     */
    public function getManifestContents(string $owner, string $repositoryName, string $branchName): ?string;

    /**
     * Returns contents of composer.json file for the given repository.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return array
     * @throws \LogicException when the composer.json is not valid json
     */
    public function getComposerContents(string $owner, string $repositoryName, string $branchName): array;

    /**
     * Returns contents of a file for the given repository.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     * @param string $filename
     *
     * @return string
     * @throws ExceptionInterface null when an error occurred reading the file.
     */
    public function getFileContents(string $owner, string $repositoryName, string $branchName, string $filename): ?string;
}