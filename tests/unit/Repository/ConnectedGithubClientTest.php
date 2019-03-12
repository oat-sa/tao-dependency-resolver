<?php

namespace OAT\DependencyResolver\Repository;

use Github\Api\ApiInterface;
use Github\Api\GitData;
use Github\Api\Organization;
use Github\Api\Repo;
use OAT\DependencyResolver\FileSystem\FileAccessor;
use OAT\DependencyResolver\Manifest\DependencyNamesFinder;
use OAT\DependencyResolver\Manifest\ExtensionNameFinder;
use OAT\DependencyResolver\Manifest\Parser;
use OAT\DependencyResolver\TestHelpers\LocalFileAccessor;
use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PhpParser\Parser as PhpParser;

/**
 * Sociable unit tests for GitHubRepositoryReader class.
 */
class ConnectedGithubClientTest //extends TestCase
{
    /** @var ConnectedGithubClientForTesting */
    private $subject;

    public function setUp()
    {
        $this->subject = new ConnectedGithubClientForTesting();
    }

    public function testConstructor_ReturnsConnectedGithubClient()
    {
        $this->assertInstanceOf(ConnectedGithubClient::class, $this->subject);
    }

    /**
     * @dataProvider listsToTest
     * @param array $pageResults
     * @param $expected
     */
    public function testGetRepositoryList(string $owner, array $pageResults, array $expected)
    {
        /** @var Organization $organizationApi */
        $organizationApi = $this->createMock(Organization::class);
        $organizationApi->method('repositories')->willReturnOnConsecutiveCalls(...$pageResults);
        $this->subject->setOrganizationApi($organizationApi);
        $this->assertEquals($expected, $this->subject->getRepositoryList($owner));
    }

    public function listsToTest()
    {
        $owner = 'oat-sa';
        $name1 = 'repository1';
        $name2 = 'repository2';
        $name3 = 'repository3';
        $branch1 = 'branch1';
        $branch2 = 'branch2';
        $branch3 = 'branch3';

        $props1 = ['name' => $name1, 'private' => true, 'default_branch' => $branch1];
        $props2 = ['name' => $name2, 'private' => false, 'default_branch' => $branch2];
        $props3 = ['name' => $name3, 'private' => true, 'default_branch' => $branch3];
        $repo1 = new Repository($owner, $name1, true, $branch1);
        $repo2 = new Repository($owner, $name2, false, $branch2);
        $repo3 = new Repository($owner, $name3, true, $branch3);

        return [
            'empty list' => [
                $owner,
                [[]],
                []
            ],
            'one result' => [
                $owner,
                [[$props1]],
                [$owner . '/' . $name1 => $repo1],
            ],
            'two pages result' => [
                $owner,
                [[$props1, $props2], [$props3]],
                [$owner . '/' . $name1 => $repo1, $owner . '/' . $name2 => $repo2, $owner . '/' . $name3 => $repo3],
            ],
        ];
    }

    public function testConstructor_ReturnsGitHubRepositoryReaderWithFileAccessor()
    {
        $this->assertInstanceOf(RepositoryReaderInterface::class, $this->subject);
        $this->assertInstanceOf(FileAccessor::class, $this->getPrivateProperty($this->subject, 'fileAccessor'));
    }

    public function testGetFileContents_WithNonExistingFile_ReturnsEmptyString()
    {
        $this->assertEquals('', $this->subject->getFileContents('oat-sa/generis', 'develop', 'non-existing-file.php'));
    }

    public function testGetFileContents_WithExistingFile_ReturnsFileContents()
    {
        $this->assertEquals(
            file_get_contents(__DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/generis/develop/manifest.php'),
            $this->subject->getFileContents('oat-sa/generis', 'develop', 'manifest.php')
        );
    }

    public function testGetComposerContents_WithNonExistingFile_ThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('composer.json of repository "oat-sa/not-existing-repository" does not exist.');
        $this->subject->getComposerContents('oat-sa/not-existing-repository', 'develop');
    }

    public function testGetComposerContents_WithNonJsonValidFile_ThrowsException()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('composer.json of repository "wrong-repo" is not valid json.');
        $this->subject->getComposerContents('wrong-repo', 'develop');
    }

    public function testGetComposerContents_WithvalidJsonFile_ReturnsArray()
    {
        $this->assertEquals(
            json_decode(file_get_contents(__DIR__ . '/../../resources/raw.githubusercontent.com/oat-sa/tao-core/develop/composer.json'), true),
            $this->subject->getComposerContents('oat-sa/tao-core', 'develop')
        );
    }

    public function testGetManifestContents()
    {
        return [
            'no dependency' => [
                'generis', 'oat-sa/generis', 'develop',
                [],
            ],
            'one dependency' => [
                'tao', 'oat-sa/tao-core', 'develop',
                ['generis'],
            ],
            'two direct dependencies' => [
                'taoBackOffice', 'oat-sa/extension-tao-backoffice', 'develop',
                ['tao', 'generis'],
            ],
            'three direct dependencies and one transitive dependency' => [
                'taoQtiItem', 'oat-sa/extension-tao-itemqti', 'develop',
                ['taoItems', 'tao', 'generis'],
            ],
        ];
    }
}

class ConnectedGithubClientForTesting extends ConnectedGithubClient
{
    const REPOSITORIES_PER_PAGE = 2;

    /** @var Organization */
    private $organizationApi;

    /** @var Repo */
    private $repositoryApi;

    /** @var GitData */
    private $gitDataApi;

    public function getOrganizationApi(): Organization
    {
        return $this->organizationApi;
    }

    /**
     * @param Organization $organizationApi
     * @return $this
     */
    public function setOrganizationApi(Organization $organizationApi): self
    {
        $this->organizationApi = $organizationApi;
        return $this;
    }

    public function getRepositoryApi(): Repo
    {
        return $this->repositoryApi;
    }

    /**
     * @param Repo $repositoryApi
     * @return $this
     */
    public function setRepositoryApi(Repo $repositoryApi): self
    {
        $this->repositoryApi = $repositoryApi;
        return $this;
    }

    public function getGitDataApi(): GitData
    {
        return $this->gitDataApi;
    }

    /**
     * @param GitData $gitDataApi
     * @return $this
     */
    public function setGitDataApi(GitData $gitDataApi): self
    {
        $this->gitDataApi = $gitDataApi;
        return $this;
    }
}
