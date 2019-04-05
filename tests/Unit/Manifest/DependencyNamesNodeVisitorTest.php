<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Manifest;

use OAT\DependencyResolver\Manifest\DependencyNamesNodeVisitor;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;

class DependencyNamesNodeVisitorTest extends TestCase
{
    /** @var DependencyNamesNodeVisitor */
    private $subject;

    public function setUp()
    {
        $this->subject = new DependencyNamesNodeVisitor();
    }

    public function testConstructorReturnsDependencyNamesNodeVisitorWithEmptyExtensionNames()
    {
        $this->assertInstanceOf(DependencyNamesNodeVisitor::class, $this->subject);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testClearReturnsDependencyNamesNodeVisitorWithEmptyExtensionNames()
    {
        $this->assertInstanceOf(DependencyNamesNodeVisitor::class, $this->subject->clear());
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithNonArrayItemNodeReturnsEmptyExtensionNames()
    {
        $node = new String_('');
        $this->subject->enterNode($node);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithNonStringKeyNodeReturnsEmptyExtensionNames()
    {
        $value = new String_('');
        $key = new Array_();
        $node = new ArrayItem($value, $key);
        $this->subject->enterNode($node);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithEmptyKeyNodeReturnsEmptyExtensionNames()
    {
        $value = new String_('');
        $key = new String_('');
        $node = new ArrayItem($value, $key);
        $this->subject->enterNode($node);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithNonArrayInRequiresKeyNodeReturnsEmptyExtensionNames()
    {
        $value = new String_('');
        $key = new String_(DependencyNamesNodeVisitor::REQUIRES_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->subject->enterNode($node);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithNoExtensionReturnsEmptyExtensionNames()
    {
        $value = new Array_();
        $key = new String_(DependencyNamesNodeVisitor::REQUIRES_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);
        $this->subject->enterNode($node);
        $this->assertEquals([], $this->subject->getResult());
    }

    public function testEnterNodeWithExtensionReturnsExtensionNames()
    {
        $extensions = $this->generateExtensionItems(2);

        $value = new Array_(array_values($extensions));
        $key = new String_(DependencyNamesNodeVisitor::REQUIRES_AST_TOKEN_KEY);
        $node = new ArrayItem($value, $key);

        $this->subject->enterNode($node);
        $this->assertEquals(array_keys($extensions), $this->subject->getResult());
    }

    /**
     * Generates an array of extension items to provide as a manifest php array node.
     *
     * @param int $number
     *
     * @return array
     */
    private function generateExtensionItems(int $number) : array
    {
        $items = [];
        for ($i = 0; $i < $number; $i++) {
            $extensionName = 'extension name ' . ($i + 1);
            $extensionVersion = 'extension version ' . ($i + 1);
            $extension = new ArrayItem(new String_($extensionVersion), new String_($extensionName));
            $items[$extensionName] = $extension;
        }

        return $items;
    }
}
