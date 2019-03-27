<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Repository;

use OAT\DependencyResolver\Manifest\DependencyNamesFinder;
use OAT\DependencyResolver\Manifest\ExtensionNameFinder;
use OAT\DependencyResolver\Manifest\Parser;
use OAT\DependencyResolver\Repository\ConnectedGithubClient;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Exception\BranchNotFoundException;
use OAT\DependencyResolver\Repository\Exception\EmptyRepositoryException;
use OAT\DependencyResolver\Repository\Exception\FileNotFoundException;
use OAT\DependencyResolver\Repository\GitHubRepositoryReader;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser as PhpParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Sociable unit tests for GitHubRepositoryReader class.
 */
class GitHubRepositoryReaderTest extends TestCase
{
    use ProtectedAccessorTrait;

    const REPOSITORY_LIST = ['some repositories'];

    /** @var GitHubRepositoryReader */
    private $subject;

    /** @var ConnectedGithubClient|MockObject */
    private $connectedGithubClient;

    public function setUp()
    {
        $this->connectedGithubClient = $this->createMock(ConnectedGithubClient::class);
        $this->connectedGithubClient->method('getContents')->willReturnCallback(
            function ($owner, $repositoryName, $branchName, $filename) {
                $filePath = __DIR__
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                    . DIRECTORY_SEPARATOR . $owner
                    . DIRECTORY_SEPARATOR . $repositoryName
                    . DIRECTORY_SEPARATOR . $branchName
                    . DIRECTORY_SEPARATOR . $filename;
                if (! file_exists($filePath)) {
                    throw new FileNotFoundException(
                        sprintf(
                            'File "%s" not found in branch "%s" of repository "%s/%s".',
                            $filename,
                            $branchName,
                            $owner,
                            $repositoryName
                        )
                    );
                }

                return file_get_contents($filePath);
            }
        );

        $phpParser = new PhpParser\Php5(new Lexer());
        $parser = new Parser($phpParser, new ExtensionNameFinder(), new DependencyNamesFinder(), new NodeTraverser());

        $this->subject = new GitHubRepositoryReader($this->connectedGithubClient, $parser);

        $this->subject->setLogger(new NullLogger());
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorReturnsGitHubRepositoryReaderWithConnectedGithubClient()
    {
        $this->assertInstanceOf(RepositoryReaderInterface::class, $this->subject);
        $this->assertInstanceOf(
            ConnectedGithubClient::class,
            $this->getPrivateProperty($this->subject, 'connectedGithubClient')
        );
        $this->assertInstanceOf(Parser::class, $this->getPrivateProperty($this->subject, 'parser'));
    }

    public function testGetOrganizationProperties()
    {
        $userName = 'name of the user';
        $properties = ['properties'];
        $this->connectedGithubClient->method('getOrganizationProperties')->with($userName)->willReturn($properties);

        $this->assertEquals($properties, $this->subject->getOrganizationProperties($userName));
    }

    public function testGetRepositoryList()
    {
        $userName = 'name of the user';
        $repositories = ['repositories'];
        $this->connectedGithubClient->method('getRepositoryList')->willReturn($repositories);

        $this->assertEquals($repositories, $this->subject->getRepositoryList($userName));
    }

    public function testFindBranchWithNotExistingBranchReturnsEmptyArray()
    {
        $owner = 'name of the owner';
        $repositoryName = 'name of the repository';
        $branchName = 'name of the branch';

        /** @var Repository $repository */
        $repository = $this->createConfiguredMock(
            Repository::class,
            ['getOwner' => $owner, 'getName' => $repositoryName]
        );

        $this->connectedGithubClient->method('getBranchReference')->with($owner, $repositoryName)
            ->willThrowException(new BranchNotFoundException(''));

        $this->assertEquals([], $this->subject->findBranch($repository, $branchName));
    }

    public function testFindBranchWithEmptyRepositoryThrowsException()
    {
        $owner = 'name of the owner';
        $repositoryName = 'name of the repository';
        $branchName = 'name of the branch';

        /** @var Repository $repository */
        $repository = $this->createConfiguredMock(
            Repository::class,
            ['getOwner' => $owner, 'getName' => $repositoryName]
        );

        $this->connectedGithubClient->method('getBranchReference')->with($owner, $repositoryName)
            ->willThrowException(new EmptyRepositoryException(''));

        $this->expectException(EmptyRepositoryException::class);
        $this->subject->findBranch($repository, $branchName);
    }

    public function testFindBranchWithExistingBranchReturnsBranchNameAndReference()
    {
        $owner = 'name of the owner';
        $repositoryName = 'name of the repository';
        $branchName = 'name of the branch';
        $branchReference = 'reference of the branch';

        /** @var Repository $repository */
        $repository = $this->createConfiguredMock(
            Repository::class,
            ['getOwner' => $owner, 'getName' => $repositoryName]
        );

        $this->connectedGithubClient->method('getBranchReference')->with($owner, $repositoryName)
            ->willReturn($branchReference);

        $this->assertEquals([$branchName => $branchReference], $this->subject->findBranch($repository, $branchName));
    }

    public function testGetManifestContentsWithNonExistingFileThrowsException()
    {
        $owner = 'oat-sa';
        $repositoryName = 'not-existing-repository';
        $branchName = 'develop';

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'File "%s" not found in branch "%s" of repository "%s/%s".',
                $this->subject::MANIFEST_FILENAME,
                $branchName,
                $owner,
                $repositoryName
            )
        );
        $this->subject->getManifestContents($owner, $repositoryName, $branchName);
    }

    public function testGetManifestContentsWithExistingFileReturnsString()
    {
        $owner = 'oat-sa';
        $repositoryName = 'tao-core';
        $branchName = 'develop';

        $this->assertEquals(
            file_get_contents(
                __DIR__
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'resources'
                . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                . DIRECTORY_SEPARATOR . $owner
                . DIRECTORY_SEPARATOR . $repositoryName
                . DIRECTORY_SEPARATOR . $branchName
                . DIRECTORY_SEPARATOR . $this->subject::MANIFEST_FILENAME
            ),
            $this->subject->getManifestContents($owner, $repositoryName, $branchName)
        );
    }

    public function testGetComposerContentsWithNonExistingFileThrowsException()
    {
        $owner = 'oat-sa';
        $repositoryName = 'not-existing-repository';
        $branchName = 'develop';

        $this->expectException(FileNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                'File "%s" not found in branch "%s" of repository "%s/%s".',
                $this->subject::COMPOSER_FILENAME,
                $branchName,
                $owner,
                $repositoryName
            )
        );
        $this->subject->getComposerContents($owner, $repositoryName, $branchName);
    }

    public function testGetComposerContentsWithNonJsonValidFileThrowsException()
    {
        $owner = 'oat-sa';
        $repositoryName = 'wrong-repo';
        $branchName = 'develop';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'File "%s" in branch "%s" of repository "%s/%s" is not valid json.',
                $this->subject::COMPOSER_FILENAME,
                $branchName,
                $owner,
                $repositoryName
            )
        );
        $this->subject->getComposerContents($owner, $repositoryName, $branchName);
    }

    public function testGetComposerContentsWithValidJsonFileReturnsArray()
    {
        $owner = 'oat-sa';
        $repositoryName = 'tao-core';
        $branchName = 'develop';

        $this->assertEquals(
            json_decode(
                file_get_contents(
                    __DIR__
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                    . DIRECTORY_SEPARATOR . $owner
                    . DIRECTORY_SEPARATOR . $repositoryName
                    . DIRECTORY_SEPARATOR . $branchName
                    . DIRECTORY_SEPARATOR . $this->subject::COMPOSER_FILENAME
                ),
                true
            ),
            $this->subject->getComposerContents($owner, $repositoryName, $branchName)
        );
    }

    public function testGetFileContentsWithNonExistingFileThrowsException()
    {
        $this->expectException(FileNotFoundException::class);
        $this->assertEquals(
            '',
            $this->subject->getFileContents('oat-sa', 'generis', 'develop', 'non-existing-file.php')
        );
    }

    public function testGetFileContentsWithExistingFileReturnsFileContents()
    {
        $this->assertEquals(
            file_get_contents(
                __DIR__
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . '..'
                . DIRECTORY_SEPARATOR . 'resources'
                . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                . DIRECTORY_SEPARATOR . 'oat-sa'
                . DIRECTORY_SEPARATOR . 'generis'
                . DIRECTORY_SEPARATOR . 'develop'
                . DIRECTORY_SEPARATOR . 'manifest.php'
            ),
            $this->subject->getFileContents('oat-sa', 'generis', 'develop', 'manifest.php')
        );
    }
}
