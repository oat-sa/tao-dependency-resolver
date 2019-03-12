<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\TestHelpers\ProtectedAccessorTrait;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser as PhpCodeParser;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    use ProtectedAccessorTrait;

    /** @var Parser */
    private $subject;

    public function setUp()
    {
        $phpParser = new PhpCodeParser\Php5(new Lexer());
        $extensionNameFinder = new ExtensionNameFinder();
        $dependencyNamesFinder = new DependencyNamesFinder();
        $traverser = new NodeTraverser();
        $this->subject = new Parser($phpParser, $extensionNameFinder, $dependencyNamesFinder, $traverser);
    }

    public function testConstructor_ReturnsRemoteManifestReader()
    {
        $this->assertInstanceOf(Parser::class, $this->subject);
        $this->assertInstanceOf(PhpCodeParser::class, $this->getPrivateProperty($this->subject, 'phpParser'));
        $this->assertInstanceOf(ExtensionNameFinder::class, $this->getPrivateProperty($this->subject, 'extensionNameFinder'));
        $this->assertInstanceOf(DependencyNamesFinder::class, $this->getPrivateProperty($this->subject, 'dependencyNamesFinder'));
        $this->assertInstanceOf(NodeTraverserInterface::class, $this->getPrivateProperty($this->subject, 'nodeTraverser'));
    }

    /**
     * @dataProvider extensionsToTest
     * @param string $extensionName
     * @param string $expectedName
     * @param array $expectedDependencyNames
     */
    public function testGetExtensionName($extensionName, $expectedName, $expectedDependencyNames)
    {
        $manifestFileName = __DIR__ . '/../../resources/raw.githubusercontent.com/' . $extensionName . '/develop/manifest.php';
        $manifestContents = file_get_contents($manifestFileName);
        $this->assertEquals($expectedName, $this->subject->getExtensionName($manifestContents));
    }

    /**
     * @dataProvider extensionsToTest
     * @param string $extensionName
     * @param string $expectedName
     * @param array $expectedDependencyNames
     */
    public function testGetDependencyNames($extensionName, $expectedName, $expectedDependencyNames)
    {
        $manifestFileName = __DIR__ . '/../../resources/raw.githubusercontent.com/' . $extensionName . '/develop/manifest.php';
        $manifestContents = file_get_contents($manifestFileName);
        $this->assertEquals($expectedDependencyNames, $this->subject->getDependencyNames($manifestContents));
    }

    public function extensionsToTest()
    {
        return [
            'no dependency' => ['oat-sa/generis', 'generis', []],
            'one dependency' => ['oat-sa/tao-core', 'tao', ['generis']],
            'two direct dependencies' => ['oat-sa/extension-tao-backoffice', 'taoBackOffice', ['tao', 'generis']],
            'three direct dependencies and one transitive dependency' => ['oat-sa/extension-tao-itemqti', 'taoQtiItem', ['taoItems', 'tao', 'generis']],
        ];
    }
}
