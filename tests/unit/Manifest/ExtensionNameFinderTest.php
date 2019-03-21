<?php

declare(strict_types=1);

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

    public function testConstructorReturnsExtensionNameFinderWithEmptyExtensionName()
    {
        $this->assertInstanceOf(ExtensionNameFinder::class, $this->subject);
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testClearReturnsExtensionNameFinderWithEmptyExtensionName()
    {
        $this->assertInstanceOf(ExtensionNameFinder::class, $this->subject->clear());
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNodeWithNonArrayItemNodeReturnsEmptyExtensionName()
    {
        $node = new String_('');
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNodeWithNonStringKeyNodeReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new Array_();
        $node = new ArrayItem($value, $key);
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNodeWithNonNameKeyNodeReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new String_('');
        $node = new ArrayItem($value, $key);
        $this->assertNull($this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNodeWithEmptyNameReturnsEmptyExtensionName()
    {
        $value = new String_('');
        $key = new String_($this->subject::NAME_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->assertEquals(NodeTraverser::STOP_TRAVERSAL, $this->subject->enterNode($node));
        $this->assertEquals('', $this->subject->getResult());
    }

    public function testEnterNodeWithExtensionReturnsExtensionName()
    {
        $extensionName = 'name of the extension';

        $value = new String_($extensionName);
        $key = new String_($this->subject::NAME_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->assertEquals(NodeTraverser::STOP_TRAVERSAL, $this->subject->enterNode($node));
        $this->assertEquals($extensionName, $this->subject->getResult());
    }
}
