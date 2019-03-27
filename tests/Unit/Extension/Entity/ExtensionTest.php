<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Extension\Entity;

use OAT\DependencyResolver\Extension\Entity\Extension;
use PHPUnit\Framework\TestCase;

class ExtensionTest extends TestCase
{
    public function testConstructorReturnsExtension()
    {
        $extensionName = 'extensionName';
        $repositoryName = 'repositoryName';
        $composerName = 'composerName';
        $branchName = 'branchName';

        $subject = new Extension($extensionName, $repositoryName, $composerName, $branchName);
        $this->assertInstanceOf(Extension::class, $subject);
        $this->assertEquals($extensionName, $subject->getExtensionName());
        $this->assertEquals($repositoryName, $subject->getRepositoryName());
        $this->assertEquals($composerName, $subject->getComposerName());
        $this->assertEquals($branchName, $subject->getBranchName());
        $this->assertEquals('dev-branchName', $subject->getPrefixedBranchName());
    }
}
