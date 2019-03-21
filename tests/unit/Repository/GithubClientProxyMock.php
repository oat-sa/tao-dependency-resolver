<?php

namespace OAT\DependencyResolver\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;

/**
 * Test mock for GithubClientProxyTest.
 */
class GithubClientProxyMock extends GithubClientProxy
{
    /** @var Organization */
    private $organizationApi;

    /** @var Repo */
    private $repositoryApi;

    /** @var GitData */
    private $gitDataApi;

    public function api($name) : ApiInterface
    {
        // Api accessor names.
        $apis = [
            self::API_ORGANIZATION => $this->organizationApi,
            self::API_REPOSITORY => $this->repositoryApi,
            self::API_REFERENCE => $this->gitDataApi,
        ];

        return $apis[$name];
    }

    /**
     * @param Organization $organizationApi
     *
     * @return $this
     */
    public function setOrganizationApi(Organization $organizationApi) : self
    {
        $this->organizationApi = $organizationApi;
        return $this;
    }

    /**
     * @param Repo $repositoryApi
     *
     * @return $this
     */
    public function setRepositoryApi(Repo $repositoryApi) : self
    {
        $this->repositoryApi = $repositoryApi;
        return $this;
    }

    /**
     * @param GitData $gitDataApi
     *
     * @return $this
     */
    public function setGitDataApi(GitData $gitDataApi) : self
    {
        $this->gitDataApi = $gitDataApi;
        return $this;
    }
}
