<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Test\Unit\Extension;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use PHPUnit\Framework\TestCase;

class ExtensionCollectionTest extends TestCase
{
    public function testEmptyCollection()
    {
        $subject = new ExtensionCollection();

        $this->assertCount(0, $subject->all());
    }

    public function testItCanBeConstructedWithExtensions()
    {
        $extension1 = new Extension('extensionName1', 'repositoryName1', 'branchName1');
        $extension2 = new Extension('extensionName2', 'repositoryName2', 'branchName2');

        $subject = new ExtensionCollection([
            $extension1,
            $extension2
        ]);

        $this->assertCount(2, $subject->all());
        $this->assertEquals([$extension1, $extension2], $subject->all());
    }

    public function testItCanAddAndRetrieveExtension()
    {
        $subject = new ExtensionCollection();

        $extension = new Extension('extensionName', 'repositoryName', 'branchName');

        $subject->add($extension);

        $this->assertSame($extension, $subject->get('extensionName'));
        $this->assertNull($subject->get('invalid'));
    }

    public function testItCanCheckIfAnExtensionExists()
    {
        $subject = new ExtensionCollection();

        $extension = new Extension('extensionName', 'repositoryName', 'branchName');

        $subject->add($extension);

        $this->assertTrue($subject->has('extensionName'));
        $this->assertFalse($subject->has('invalid'));
    }
}
