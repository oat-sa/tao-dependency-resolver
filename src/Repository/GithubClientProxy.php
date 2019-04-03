<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Client;
use Github\Exception\ErrorException;
use Github\Exception\InvalidArgumentException;

class GithubClientProxy extends Client
{
    /** @var Organization|ApiInterface */
    protected $organizationApi;

    /** @var Repo|ApiInterface */
    protected $repositoryApi;

    /** @var GitData|ApiInterface */
    protected $gitDataApi;

    // Api accessor names.
    protected const API_ORGANIZATION = 'organization';
    protected const API_REPOSITORY = 'repo';
    protected const API_REFERENCE = 'gitData';

    public function getOrganizationProperties(string $owner): array
    {
        return $this->getOrganizationApi()->show($owner);
    }

    /**
     * Returns a page of the repository list of the given owner
     *
     * @param string $owner
     * @param int    $page
     * @param int    $perPage
     *
     * @return array
     */
    public function getRepositoryList(string $owner, int $page, int $perPage): array
    {
        $organisationApi = $this->getOrganizationApi();
        $organisationApi->setPerPage($perPage);

        return $organisationApi->repositories($owner, 'all', $page);
    }

    /**
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchReference
     * @param string $filename
     *
     * @return string|null
     * @throws InvalidArgumentException If $path is not a file or if its encoding is different from base64
     * @throws ErrorException           If $path doesn't include a 'content' index
     */
    public function getFileContents(
        string $owner,
        string $repositoryName,
        string $branchReference,
        string $filename
    ): ?string {
        return $this->getRepositoryApi()->contents()->download($owner, $repositoryName, $filename, $branchReference);
    }

    /**
     * Checks existence of a branch.
     *
     * @param string $owner
     * @param string $repositoryName
     * @param string $branchName
     *
     * @return array
     */
    public function getReference(string $owner, string $repositoryName, string $branchName): array
    {
        return $this->getGitDataApi()->references()->show($owner, $repositoryName, 'heads/' . $branchName);
    }

    /**
     * @return Organization|ApiInterface
     */
    public function getOrganizationApi(): Organization
    {
        if ($this->organizationApi === null) {
            $this->organizationApi = $this->api(self::API_ORGANIZATION);
        }

        return $this->organizationApi;
    }

    /**
     * @return Repo|ApiInterface
     */
    public function getRepositoryApi(): Repo
    {
        if ($this->repositoryApi === null) {
            $this->repositoryApi = $this->api(self::API_REPOSITORY);
        }

        return $this->repositoryApi;
    }

    /**
     * @return GitData|ApiInterface
     */
    public function getGitDataApi(): GitData
    {
        if ($this->gitDataApi === null) {
            $this->gitDataApi = $this->api(self::API_REFERENCE);
        }

        return $this->gitDataApi;
    }
}
