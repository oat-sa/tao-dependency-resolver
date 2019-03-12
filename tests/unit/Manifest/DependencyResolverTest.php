<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Extension\NotMappedException;
use OAT\DependencyResolver\Repository\ConnectedGithubClient;
use OAT\DependencyResolver\Repository\GitHubRepositoryReader;
use OAT\DependencyResolver\Repository\RepositoryReaderInterface;
use OAT\DependencyResolver\TestHelpers\LocalFileAccessor;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser as PhpCodeParser;
use PHPUnit\Framework\TestCase;
use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;

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
        'taoQtiTest' => 'oat-sa/extension-tao-testqti'
    ];

    /** @var DependencyResolver */
    private $subject;

    /** @var ExtensionFactory */
    private $extensionFactory;

    public function setUp()
    {
        $this->subject = $this->createDependencyResolver();
    }

    public function testConstructor_ReturnsDependencyResolverInstance()
    {
        $this->assertInstanceOf(DependencyResolver::class, $this->subject);
        $this->assertInstanceOf(RepositoryReaderInterface::class, $this->getPrivateProperty($this->subject, 'repositoryReader'));
        $this->assertInstanceOf(Parser::class, $this->getPrivateProperty($this->subject, 'parser'));
        $this->assertInstanceOf(ExtensionFactory::class, $this->getPrivateProperty($this->subject, 'extensionFactory'));
    }

    /**
     * @dataProvider extensionsToTest
     * @param array $expectedExtensions
     * @param string $rootExtension
     * @param string $rootBranch
     * @param array $extensionBranchMap
     * @throws \OAT\DependencyResolver\Extension\NotMappedException
     */
    public function testResolve(array $expectedExtensions, string $rootExtension, string $rootBranch = Extension::DEFAULT_BRANCH, array $extensionBranchMap = [])
    {
        $rootExtension = $this->extensionFactory->create($rootExtension, $rootBranch);
        $actual = $this->subject->resolve($rootExtension, $extensionBranchMap);

        $expected = new ExtensionCollection();
        foreach ($expectedExtensions as $expectedExtension => $expectedBranch) {
            $expected->add($this->extensionFactory->create($expectedExtension, $expectedBranch));
        }

        $this->assertEquals($expected, $actual);
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
                'rootBranch',
                ['taoItems' => $customBranch, 'tao' => $default],
            ],
        ];
    }

    public function testExtractExtensionsRecursively_WithNotMappedExtension_ThrowsException()
    {
        $this->markTestIncomplete();
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
     * @return DependencyResolver
     */
    private function createDependencyResolver($extensionMap = self::EXTENSION_MAP)
    {
        $fileAccessor = new LocalFileAccessor();
        $phpParser = new PhpCodeParser\Php5(new Lexer());
        $parser = new Parser($phpParser, new ExtensionNameFinder(), new DependencyNamesFinder(), new NodeTraverser());

        /** @var ConnectedGithubClient $connectedGithubClient */
        $connectedGithubClient = $this->createMock(ConnectedGithubClient::class);

        $repositoryReader = new GitHubRepositoryReader($connectedGithubClient, $parser);
        $this->extensionFactory = new ExtensionFactory($extensionMap);

        return new DependencyResolver($repositoryReader, $parser, $this->extensionFactory);
    }
}
