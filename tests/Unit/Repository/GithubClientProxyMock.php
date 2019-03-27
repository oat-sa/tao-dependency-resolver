<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;
use OAT\DependencyResolver\Repository\GithubClientProxy;

/**
 * Test mock for GithubClientProxyTest.
 */
class GithubClientProxyMock extends GithubClientProxy
{
    /** @var Organization */
    protected $organizationApi;

    /** @var Repo */
    protected $repositoryApi;

    /** @var GitData */
    protected $gitDataApi;

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
