<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use PHPUnit\Framework\TestCase;

class ExtensionCollectionTest extends TestCase
{
    /**
     * @var ExtensionCollection
     */
    private $subject;

    public function setUp()
    {
        $this->subject = new ExtensionCollection();
    }

    public function testConstructor_ReturnsAnEmptyCollection()
    {
        $this->assertInstanceOf(ExtensionCollection::class, $this->subject);
        $this->assertCount(0, $this->subject->getIterator());
    }

    public function testAdd_WithValidExtension_ReturnsCollectionWithOneExtension()
    {
        $extensionName = 'extensionName';
        /** @var Extension $extension */
        $extension = $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName]);

        $this->assertInstanceOf(ExtensionCollection::class, $this->subject->add($extension));

        $this->assertCount(1, $this->subject->getIterator());
        $this->assertTrue($this->subject->offsetExists($extensionName));
        $this->assertEquals($extension, $this->subject->offsetGet($extensionName));
    }

    public function testOffsetSet_WithInvalidExtension_ThrowsException()
    {
        $extensionName = 'extensionName';
        $extension = 'not an Extension object';
        $this->expectException(\TypeError::class);
        $this->subject->offsetSet($extensionName, $extension);
    }

    public function testOffsetGet_WithNotExistingExtension_ReturnsNull()
    {
        $extensionName = 'extensionName';
        /** @var Extension $extension */
        $extension = $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName]);
        $this->assertNull($this->subject->offsetget($extensionName . 'blah'));
    }

    public function testGetIterator_WithExtensions_ReturnsIteratorOnExtensions()
    {
        $extensionName1 = 'extensionName1';
        /** @var Extension $extension1 */
        $extension1 = $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName1]);
        $extensionName2 = 'extensionName2';
        /** @var Extension $extension2 */
        $extension2 = $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName2]);

        $this->subject
            ->add($extension1)
            ->add($extension2);

        $iterator = $this->subject->getIterator();
        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertCount(2, $iterator);
        $extensionArray = [];
        foreach ($iterator as $extension) {
            $extensionArray[] = $extension;
        }
        $this->assertEquals([$extension1, $extension2], $extensionArray);
    }
}
