<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeTraverser;
use PHPUnit\Framework\TestCase;

class ExtensionNameFinderTest extends TestCase
{
    /** @var ExtensionNameFinder */
    private $subject;

    public function setUp()
    {
        $this->subject = new ExtensionNameFinder();
    }

    public function testConstructor_ReturnsExtensionNameFinderWithEmptyExtensionName()
    {
        $this->assertInstanceOf(ExtensionNameFinder::class, $this->subject);
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testClear_ReturnsExtensionNameFinderWithEmptyExtensionName()
    {
        $this->assertInstanceOf(ExtensionNameFinder::class, $this->subject->clear());
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNode_WithNonArrayItemNode_ReturnsEmptyExtensionName()
    {
        $node = new String_('');
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNode_WithNonStringKeyNode_ReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new Array_();
        $node = new ArrayItem($value, $key);
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNode_WithNonNameKeyNode_ReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new String_('');
        $node = new ArrayItem($value, $key);
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNode_WithEmptyName_ReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new String_($this->subject::NAME_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->assertEquals(NodeTraverser::STOP_TRAVERSAL, $this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNode_WithExtension_ReturnsExtensionName()
    {
        $extensionName = 'name of the extension';

        $value = new String_($extensionName);
        $key = new String_($this->subject::NAME_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->assertEquals(NodeTraverser::STOP_TRAVERSAL, $this->subject->enterNode($node));
        $this->assertEquals($extensionName, $this->subject->getResult());
    }
}
