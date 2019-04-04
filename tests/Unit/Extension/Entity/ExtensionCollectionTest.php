<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Extension\Entity;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Entity\ExtensionCollection;
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

    public function testConstructorReturnsAnEmptyCollection()
    {
        $this->assertInstanceOf(ExtensionCollection::class, $this->subject);
        $this->assertCount(0, $this->subject->getIterator());
    }

    public function testAddWithValidExtensionReturnsCollectionWithOneExtension()
    {
        $extensionName = 'extensionName';
        /** @var Extension $extension */
        $extension = $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName]);

        $this->assertInstanceOf(ExtensionCollection::class, $this->subject->add($extension));

        $this->assertCount(1, $this->subject->getIterator());
        $this->assertTrue($this->subject->has($extensionName));
    }

    public function testOffsetSetWithInvalidExtensionThrowsException()
    {
        $extension = 'not an Extension object';
        $this->expectException(\TypeError::class);
        $this->subject->add($extension);
    }

    public function testOffsetGetWithNotExistingExtensionReturnsNull()
    {
        $extensionName = 'extensionName';
        /** @var Extension $extension */
        $this->createConfiguredMock(Extension::class, ['getExtensionName' => $extensionName]);
        $this->assertFalse($this->subject->has($extensionName . 'blah'));
    }

    public function testGetIteratorWithExtensionsReturnsIteratorOnExtensions()
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

    public function testJsonSerialize()
    {
        $extensionName1 = 'extensionName1';
        $reposirotyName1 = 'name of repo 1';
        $branchName1 = 'name of branch 1';
        $extension1 = new Extension($extensionName1, $reposirotyName1, $branchName1);

        $extensionName2 = 'extensionName2';
        $reposirotyName2 = 'name of repo 2';
        $branchName2 = 'name of branch 2';
        $extension2 = new Extension($extensionName2, $reposirotyName2, $branchName2);

        $this->subject
            ->add($extension1)
            ->add($extension2);

        $expected = '{
    "require": {
        "' . $reposirotyName1 . '": "dev-' . $branchName1 . '",
        "' . $reposirotyName2 . '": "dev-' . $branchName2 . '"
    }
}';

        $this->assertEquals($expected, json_encode($this->subject, JSON_PRETTY_PRINT));
    }
}
