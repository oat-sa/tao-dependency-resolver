<?php

namespace OAT\DependencyResolver\Repository;

use Github\Exception\RuntimeException;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Exception\BranchNotFoundException;
use OAT\DependencyResolver\Repository\Exception\EmptyRepositoryException;
use OAT\DependencyResolver\Repository\Exception\FileNotFoundException;
use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Sociable unit tests for GitHubRepositoryReader class.
 */
class ConnectedGithubClientTest extends TestCase
{
    use ProtectedAccessorTrait;

    /** @var ConnectedGithubClient */
    private $subject;

    /** @var GithubClientProxy|MockObject */
    private $client;

    public function setUp()
    {
        $this->client = $this->createMock(GithubClientProxy::class);
        $this->subject = new ConnectedGithubClient($this->client);
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorReturnsConnectedGithubClientWithEmptyToken()
    {
        $this->assertInstanceOf(ConnectedGithubClient::class, $this->subject);
        $this->assertEquals($this->client, $this->getPrivateProperty($this->subject, 'client'));
        $this->assertEquals('', $this->getPrivateProperty($this->subject, 'token'));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSetToken()
    {
        $token = 'anything in there';
        $this->subject->setToken($token);
        $this->assertEquals($token, $this->getPrivateProperty($this->subject, 'token'));
    }

    /**
     * In fact this is really testing authenticateAndCheck protected method.
     *
     * @throws \ReflectionException
     */
    public function testGetOrganizationPropertiesWhenAlreadyAuthenticatedReturnsExistingOrganizationProperties()
    {
        $this->setPrivateProperty($this->subject, 'authenticated', true);
        $this->assertEquals([], $this->subject->getOrganizationProperties(''));
    }

    /**
     * In fact this is really testing authenticateAndCheck protected method.
     */
    public function testGetOrganizationPropertiesWithFailingAuthenticationThrowsException()
    {
        $authenticationExceptionMessage = 'blah blah blah';

        $this->client->method('getOrganizationProperties')->with('')
            ->willThrowException(new \Exception($authenticationExceptionMessage));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'A error occurred when trying to authenticate to Github API: ' . $authenticationExceptionMessage
        );
        $this->subject->getOrganizationProperties('');
    }

    /**
     * In fact this is really testing authenticateAndCheck protected method.
     */
    public function testGetOrganizationPropertiesWhenNotAuthenticatedReturnsOrganizationProperties()
    {
        $owner = 'owner name';
        $properties = ['properties'];

        $this->client->method('getOrganizationProperties')->with($owner)->willReturn($properties);

        $this->assertEquals($properties, $this->subject->getOrganizationProperties($owner));
    }

    /**
     * @dataProvider listsToTest
     *
     * @param string $owner
     * @param array  $pageResults
     * @param        $expected
     */
    public function testGetRepositoryList(string $owner, array $pageResults, array $expected)
    {
        $this->client->method('getRepositoryList')->willReturnCallback(
            function ($organization, $page, $perPage) use ($owner, $pageResults) {
                return $pageResults[$page - 1];
            }
        );

        $this->assertEquals($expected, $this->subject->getRepositoryList($owner, 2));
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
                [],
            ],
            'one result' => [
                $owner,
                [[$props1]],
                [$owner . '/' . $name1 => $repo1],
            ],
            'two pages result' => [
                $owner,
                [[$props1, $props2], [$props3]],
                [
                    $owner . '/' . $name1 => $repo1,
                    $owner . '/' . $name2 => $repo2,
                    $owner . '/' . $name3 => $repo3,
                ],
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetContentsWithNotExistingFileThrowsException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'the branch';
        $fileName = 'a name for this file';
        $branchReference = 'BranchReference';
        $reference = ['ref' => $branchReference];

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        // Assume the reference exists (tested below).
        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($reference);
        $this->client->method('getFileContents')->with($owner, $repositoryName, $branchReference, $fileName)
            ->willThrowException(new RuntimeException('message', 404));

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'File "%s" not found in branch "%s" of repository "%s/%s".',
                $fileName,
                $branchName,
                $owner,
                $repositoryName
            )
        );
        $this->subject->getContents($owner, $repositoryName, $branchName, $fileName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetContentsWithRuntimeExceptionWithAnotherCodeThrowsTheException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $fileName = 'a name for this file please';
        $branchReference = 'BranchReference';
        $reference = ['ref' => $branchReference];
        $message = 'the exception message';
        $exceptionCode = 1012;
        $exception = new RuntimeException($message, $exceptionCode);

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        // Assume the reference exists (tested below).
        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($reference);
        $this->client->method('getFileContents')->with($owner, $repositoryName, $branchReference, $fileName)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($exceptionCode);
        $this->subject->getContents($owner, $repositoryName, $branchName, $fileName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetContentsWithAnotherExceptionThrowsTheException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $fileName = 'a name for this file please';
        $branchReference = 'BranchReference';
        $reference = ['ref' => $branchReference];
        $message = 'the exception message';
        $exceptionCode = 1012;
        $exception = new \Exception($message, $exceptionCode);

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        // Assume the reference exists (tested below).
        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($reference);
        $this->client->method('getFileContents')->with($owner, $repositoryName, $branchReference, $fileName)
            ->willThrowException($exception);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($exceptionCode);
        $this->subject->getContents($owner, $repositoryName, $branchName, $fileName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetContentsWithExistingFileReturnFileContents()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $fileName = 'a name for this file please';
        $contents = 'contents of the file';
        $branchReference = 'BranchReference';
        $reference = ['ref' => $branchReference];

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        // Assume the reference exists (tested below).
        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($reference);

        $this->client->method('getFileContents')->with($owner, $repositoryName, $branchReference, $fileName)
            ->willReturn($contents);

        $this->assertEquals($contents, $this->subject->getContents($owner, $repositoryName, $branchName, $fileName));
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBranchReferenceWithEmptyRepositoryThrowsException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)
            ->willThrowException(new RuntimeException('', 409));

        $this->expectException(EmptyRepositoryException::class);
        $this->subject->getBranchReference($owner, $repositoryName, $branchName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBranchReferenceWithNotExistingBranchThrowsException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)
            ->willThrowException(new RuntimeException('', 404));

        $this->expectException(BranchNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to retrieve reference to "%s/%s/%s".', $owner, $repositoryName, $branchName)
        );
        $this->subject->getBranchReference($owner, $repositoryName, $branchName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBranchReferenceWithOtherExceptionThrowsTheException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $message = 'the exception message';
        $exceptionCode = 1012;
        $exception = new RuntimeException($message, $exceptionCode);

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)
            ->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);
        $this->expectExceptionCode($exceptionCode);
        $this->subject->getBranchReference($owner, $repositoryName, $branchName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBranchReferenceWithNotExistingBranchButExistingBranchIncludingBranchNameThrowsException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $references = [
            ['ref' => 'otherBranch1Reference'],
            ['ref' => 'otherBranch2Reference'],
        ];

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($references);

        $this->expectException(BranchNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf('Unable to retrieve reference to "%s/%s/%s".', $owner, $repositoryName, $branchName)
        );
        $this->subject->getBranchReference($owner, $repositoryName, $branchName);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGetBranchReferenceWithExistingBranchReturnsReference()
    {
        $owner = 'name of the owner';
        $repositoryName = 'the repo';
        $branchName = 'and the branch';
        $branchReference = 'BranchReference';
        $reference = ['ref' => $branchReference];

        // We just bypass the authentication (tested above).
        $this->setPrivateProperty($this->subject, 'authenticated', true);

        $this->client->method('getReference')->with($owner, $repositoryName, $branchName)->willReturn($reference);

        $this->assertEquals($branchReference, $this->subject->getBranchReference($owner, $repositoryName, $branchName));
    }
}
