<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Manifest;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Manifest\DependencyNamesNodeVisitor;
use OAT\DependencyResolver\Manifest\DependencyResolver;
use OAT\DependencyResolver\Manifest\ExtensionNameNodeVisitor;
use OAT\DependencyResolver\Manifest\Parser;
use OAT\DependencyResolver\Repository\GithubConnection;
use OAT\DependencyResolver\Repository\GitHubRepositoryReader;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser as PhpCodeParser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DependencyResolverTest extends TestCase
{
    use ProtectedAccessorTrait;
    const EXTENSION_MAP = [
        'generis' => 'oat-sa/generis',
        'package-tao' => 'oat-sa/package-tao',
        'tao' => 'oat-sa/tao-core',
        'taoBackOffice' => 'oat-sa/extension-tao-backoffice',
        'taoItems' => 'oat-sa/extension-tao-item',
        'taoQtiItem' => 'oat-sa/extension-tao-itemqti',
        'taoQtiTest' => 'oat-sa/extension-tao-testqti',
    ];

    /** @var DependencyResolver */
    private $subject;

    /** @var ExtensionFactory */
    private $extensionFactory;

    public function setUp()
    {
        $this->subject = $this->createDependencyResolver();
    }

    /**
     * @throws \ReflectionException
     */
    public function testConstructorReturnsDependencyResolverInstance()
    {
        $this->assertInstanceOf(DependencyResolver::class, $this->subject);
        $this->assertInstanceOf(
            RepositoryReaderInterface::class,
            $this->getPrivateProperty($this->subject, 'repositoryReader')
        );
        $this->assertInstanceOf(Parser::class, $this->getPrivateProperty($this->subject, 'parser'));
        $this->assertInstanceOf(ExtensionFactory::class, $this->getPrivateProperty($this->subject, 'extensionFactory'));
    }

    /**
     * @dataProvider extensionsToTest
     *
     * @param array  $expectedExtensions
     * @param string $rootExtensionName
     * @param string $rootBranch
     * @param array  $extensionBranchMap
     *
     * @throws \OAT\DependencyResolver\Extension\Exception\NotMappedException
     */
    public function testResolve(
        array $expectedExtensions,
        string $rootExtensionName,
        string $rootBranch = Extension::DEFAULT_BRANCH,
        array $extensionBranchMap = []
    ) {
        $rootExtension = $this->extensionFactory->create($rootExtensionName, $rootBranch);
        $actual = $this->subject->resolve($rootExtension, $extensionBranchMap);

        $expected = [];
        $expected[self::EXTENSION_MAP[$rootExtensionName]] = 'dev-' . $rootBranch;

        foreach ($expectedExtensions as $expectedExtension => $expectedBranch) {
            $expected[self::EXTENSION_MAP[$expectedExtension]] = 'dev-' . $expectedBranch;
        }

        $this->assertEquals(json_encode(['require' => $expected], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $actual);
    }

    public function extensionsToTest()
    {
        $default = Extension::DEFAULT_BRANCH;
        $customBranch = 'custom-branch';

        return [
            'no dependency' => [
                [],
                'generis',
            ],
            'one dependency - no branch' => [
                ['generis' => $default],
                'tao',
            ],
            'two direct dependencies' => [
                ['tao' => $default, 'generis' => $default],
                'taoBackOffice',
            ],
            'three direct dependencies and one transitive dependency' => [
                ['taoItems' => $customBranch, 'tao' => $default, 'generis' => $default],
                'taoQtiItem',
                $default,
                ['taoItems' => $customBranch, 'tao' => $default],
            ],
        ];
    }

    /**
     * @throws NotMappedException
     */
    public function testExtractExtensionsRecursivelyWithNotMappedExtensionThrowsException()
    {
        // This should return 'tao' and 'generis' extensions, let's not map 'generis'.
        $this->subject = $this->createDependencyResolver(['tao' => 'oat-sa/tao-core']);

        // Performs the recursive extraction.
        $rootExtension = $this->extensionFactory->create('tao');

        $this->expectException(NotMappedException::class);
        $this->expectExceptionMessage('Extension "generis" not found in map.');
        $this->subject->resolve($rootExtension, []);
    }

    /**
     * Creates a DependencyResolver with the given extension map.
     *
     * @param array $extensionMap
     *
     * @return DependencyResolver
     */
    private function createDependencyResolver($extensionMap = self::EXTENSION_MAP)
    {
        $phpParser = new PhpCodeParser\Php5(new Lexer());
        $parser = new Parser($phpParser, new ExtensionNameNodeVisitor(), new DependencyNamesNodeVisitor(),
            new NodeTraverser());

        /** @var GithubConnection|MockObject $connectedGithubClient */
        $connectedGithubClient = $this->createMock(GithubConnection::class);
        $connectedGithubClient->method('getContents')->willReturnCallback(
            function (string $owner, string $repositoryName, string $branchName, string $filename) {
                $filePath = __DIR__
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . '..'
                    . DIRECTORY_SEPARATOR . 'resources'
                    . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
                    . DIRECTORY_SEPARATOR . $owner
                    . DIRECTORY_SEPARATOR . $repositoryName
                    . DIRECTORY_SEPARATOR . $branchName
                    . DIRECTORY_SEPARATOR . $filename;

                return file_exists($filePath) ? file_get_contents($filePath) : null;
            }
        );

        $repositoryReader = new GitHubRepositoryReader($connectedGithubClient, $parser);
        $repositoryReader->setLogger(new NullLogger());

        $this->extensionFactory = new ExtensionFactory($extensionMap);

        $dependencyResolver = new DependencyResolver($repositoryReader, $parser, $this->extensionFactory);
        $dependencyResolver->setLogger(new NullLogger());

        return $dependencyResolver;
    }
}
