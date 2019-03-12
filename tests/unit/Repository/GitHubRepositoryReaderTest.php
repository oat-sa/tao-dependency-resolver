<?php

namespace OAT\DependencyResolver\Repository;

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
class GitHubRepositoryReaderTest //extends TestCase
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
        $phpParser = new PhpParser\Php5(new Lexer());
        $parser = new Parser($phpParser, new ExtensionNameFinder(), new DependencyNamesFinder(), new NodeTraverser());

        $this->subject = new GitHubRepositoryReader($this->connectedGithubClient, $parser);
    }

    public function testConstructor_ReturnsGitHubRepositoryListReaderWithFileAccessor()
    {
        $this->assertInstanceOf(RepositoryReaderInterface::class, $this->subject);
        $this->assertInstanceOf(ConnectedGithubClient::class, $this->getPrivateProperty($this->subject, 'connectedGithubClient'));
        $this->assertInstanceOf(Parser::class, $this->getPrivateProperty($this->subject, 'parser'));
    }

    public function testGetRepositoryList()
    {
        $this->connectedGithubClient->method('getRepositoryList')->willReturnCallback(
            function ($userName) {
                return [$userName];
            }
        );

        $userName = 'name of the user';
        $this->assertEquals([$userName], $this->subject->getRepositoryList($userName));
    }

    public function listsToTest()
    {
        $name1 = 'oat-sa/repository1';
        $name2 = 'oat-sa/repository2';
        $name3 = 'oat-sa/repository3';
        return [
            'empty list' => [[''], []],
            'one result' => [['[{"full_name": "' . $name1 . '"}]', ''], [$name1]],
            'two pages result' => [['[{"full_name": "' . $name1 . '"},{"full_name": "' . $name2 . '"}]', '[{"full_name": "' . $name3 . '"}]', ''], [$name1, $name2, $name3]],
            'non valid json on first page' => [['"i\'m broken', 'this won\'t be read', ''], []],
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