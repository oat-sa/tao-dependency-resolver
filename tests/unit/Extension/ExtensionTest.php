<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use PHPUnit\Framework\TestCase;

class ExtensionTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $extensionName = 'extensionName';
        $repositoryName = 'repositoryName';
        $branchName = 'branchName';

        $subject = new Extension($extensionName, $repositoryName, $branchName);
        $this->assertEquals($extensionName, $subject->getExtensionName());
        $this->assertEquals($repositoryName, $subject->getRepositoryName());
        $this->assertEquals($branchName, $subject->getBranchName());
        $this->assertEquals('', $subject->getComposerName());
        $this->assertEquals('dev-branchName', $subject->getPrefixedBranchName());
    }

    public function testComposerNameAccessors()
    {
        $subject = new Extension('', '', '');

        $composerName = 'composerName';
        $this->assertInstanceOf(Extension::class, $subject->setComposerName($composerName));
        $this->assertEquals($composerName, $subject->getComposerName());
    }
}
