<?php

namespace OAT\DependencyResolver\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Client;
use Github\Exception\ErrorException;

class GithubClientProxy extends Client
{
    // Api accessor names.
    const API_ORGANIZATION = 'organization';
    const API_REPOSITORY = 'repo';
    const API_REFERENCE = 'gitData';

    /**
     * Retrieves organization properties.
     *
     * @param string $owner
     *
     * @return array
     */
    public function getOrganizationProperties(string $owner): array
    {
        return $this->getOrganizationApi()->show($owner);
    }

    /**
     * Returns a page of the repository list of the given owner?
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
     * @throws ErrorException
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
        return $this->api(self::API_ORGANIZATION);
    }

    /**
     * @return Repo|ApiInterface
     */
    public function getRepositoryApi(): Repo
    {
        return $this->api(self::API_REPOSITORY);
    }

    /**
     * @return GitData|ApiInterface
     */
    public function getGitDataApi(): GitData
    {
        return $this->api(self::API_REFERENCE);
    }
}
