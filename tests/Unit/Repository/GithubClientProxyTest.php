<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository;

use Github\Api\GitData;
use Github\Api\GitData\References;
use Github\Api\Organization;
use Github\Api\Repo;
use Github\Api\Repository\Contents;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Sociable unit tests for GitHubRepositoryReader class.
 */
class GithubClientProxyTest extends TestCase
{
    use ProtectedAccessorTrait;

    /** @var GithubClientProxyMock */
    private $subject;

    public function setUp()
    {
        $this->subject = new GithubClientProxyMock();
    }

    public function testGetOrganizationProperties()
    {
        $owner = 'owner name';
        $properties = ['properties'];

        /** @var Organization|MockObject $organizationApi */
        $organizationApi = $this->createMock(Organization::class);
        $organizationApi->method('show')->with($owner)->willReturn($properties);
        $this->subject->setOrganizationApi($organizationApi);

        $this->assertEquals($properties, $this->subject->getOrganizationProperties($owner));
    }

    public function testGetRepositoryList()
    {
        $owner = 'owner name';
        $repositories = ['repository'];
        $page = 10;
        $perPage = 12;

        /** @var Organization|MockObject $organizationApi */
        $organizationApi = $this->createMock(Organization::class);
        $organizationApi->expects($this->once())->method('setPerPage')->with($perPage);
        $organizationApi->method('repositories')->with($owner, 'all', $page)->willReturn($repositories);
        $this->subject->setOrganizationApi($organizationApi);

        $this->assertEquals($repositories, $this->subject->getRepositoryList($owner, $page, $perPage));
    }

    public function testGetFileContents()
    {
        $username = 'owner';
        $repository = 'name of the repo';
        $branchReference = 'a branch reference';
        $filename = 'name of a file';
        $contents = 'your contents here';

        /** @var Contents|MockObject $referenceApi */
        $contentsApi = $this->createMock(Contents::class);
        $contentsApi->method('download')->with($username, $repository, $filename, $branchReference)
            ->willReturn($contents);

        /** @var Repo $repositoryApi */
        $repositoryApi = $this->createConfiguredMock(Repo::class, ['contents' => $contentsApi]);
        $this->subject->setRepositoryApi($repositoryApi);

        $this->assertEquals(
            $contents,
            $this->subject->getFileContents($username, $repository, $branchReference, $filename)
        );
    }

    public function testGetReference()
    {
        $username = 'owner';
        $repository = 'name of the repo';
        $branch = 'a branch name';
        $reference = ['ref' => 'a reference'];

        /** @var References|MockObject $referenceApi */
        $referenceApi = $this->createMock(References::class);
        $referenceApi->method('show')->with($username, $repository, 'heads/' . $branch)->willReturn($reference);

        /** @var GitData $gitDataApi */
        $gitDataApi = $this->createConfiguredMock(GitData::class, ['references' => $referenceApi]);
        $this->subject->setGitDataApi($gitDataApi);

        $this->assertEquals($reference, $this->subject->getReference($username, $repository, $branch));
    }

    public function testGetOrganizationApiReturnsOrganizationApi()
    {
        /** @var Organization $organizationApi */
        $organizationApi = $this->createMock(Organization::class);
        $this->subject->setOrganizationApi($organizationApi);

        $this->assertInstanceOf(Organization::class, $this->subject->getOrganizationApi());
    }

    public function testGetRepositoryApiReturnsRepositoryApi()
    {
        /** @var Repo $repositoryApi */
        $repositoryApi = $this->createMock(Repo::class);
        $this->subject->setRepositoryApi($repositoryApi);

        $this->assertInstanceOf(Repo::class, $this->subject->getRepositoryApi());
    }

    public function testGetGitDataApiReturnsGitDataApi()
    {
        /** @var GitData $gitDataApi */
        $gitDataApi = $this->createMock(GitData::class);
        $this->subject->setGitDataApi($gitDataApi);

        $this->assertInstanceOf(GitData::class, $this->subject->getGitDataApi());
    }
}
