<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Manifest;

use OAT\DependencyResolver\Manifest\ExtensionNameFinder;
use OAT\DependencyResolver\Manifest\DependencyNamesFinder;
use OAT\DependencyResolver\Manifest\Parser;
use OAT\DependencyResolver\Tests\Helpers\ProtectedAccessorTrait;
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

    /**
     * @throws \ReflectionException
     */
    public function testConstructorReturnsRemoteManifestReader()
    {
        $this->assertInstanceOf(Parser::class, $this->subject);
        $this->assertInstanceOf(PhpCodeParser::class, $this->getPrivateProperty($this->subject, 'phpParser'));
        $this->assertInstanceOf(
            ExtensionNameFinder::class,
            $this->getPrivateProperty($this->subject, 'extensionNameFinder')
        );
        $this->assertInstanceOf(
            DependencyNamesFinder::class,
            $this->getPrivateProperty($this->subject, 'dependencyNamesFinder')
        );
        $this->assertInstanceOf(
            NodeTraverserInterface::class,
            $this->getPrivateProperty($this->subject, 'nodeTraverser')
        );
    }

    /**
     * @dataProvider extensionsToTest
     *
     * @param string $extensionName
     * @param string $expectedName
     */
    public function testGetExtensionName($extensionName, $expectedName)
    {
        $manifestFileName = __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
            . DIRECTORY_SEPARATOR . $extensionName
            . DIRECTORY_SEPARATOR . 'develop'
            . DIRECTORY_SEPARATOR . 'manifest.php';
        $manifestContents = file_get_contents($manifestFileName);
        $this->assertEquals($expectedName, $this->subject->getExtensionName($manifestContents));
    }

    public function extensionsToTest()
    {
        return [
            '0 dependency' => ['oat-sa/generis', 'generis'],
            '1 dependency' => ['oat-sa/tao-core', 'tao'],
            '2 direct dependencies' => ['oat-sa/extension-tao-backoffice', 'taoBackOffice'],
            '3 direct dependencies + 1 transitive dependency' => ['oat-sa/extension-tao-itemqti', 'taoQtiItem'],
        ];
    }

    /**
     * @dataProvider dependenciesToTest
     *
     * @param string $extensionName
     * @param array  $expectedDependencyNames
     */
    public function testGetDependencyNames($extensionName, $expectedDependencyNames)
    {
        $manifestFileName = __DIR__
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'raw.githubusercontent.com'
            . DIRECTORY_SEPARATOR . $extensionName
            . DIRECTORY_SEPARATOR . 'develop'
            . DIRECTORY_SEPARATOR . 'manifest.php';
        $manifestContents = file_get_contents($manifestFileName);
        $this->assertEquals($expectedDependencyNames, $this->subject->getDependencyNames($manifestContents));
    }

    public function dependenciesToTest()
    {
        return [
            '0 dependency' => ['oat-sa/generis', []],
            '1 dependency' => ['oat-sa/tao-core', ['generis']],
            '2 direct dependencies' => ['oat-sa/extension-tao-backoffice', ['tao', 'generis']],
            '3 direct dependencies + 1 transitive dependency' => [
                'oat-sa/extension-tao-itemqti',
                ['taoItems', 'tao', 'generis'],
            ],
        ];
    }
}
