<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Tests\Unit\Extension\Entity;

use OAT\DependencyResolver\Extension\Entity\Extension;
use PHPUnit\Framework\TestCase;

class ExtensionTest extends TestCase
{
    private const EXTENSION_NAME = 'extensionName';
    private const REPOSITORY_NAME = 'repositoryName';
    private const BRANCH_NAME = 'branchName';

    /** @var Extension */
    private $subject;

    public function setUp()
    {
        $this->subject = new Extension(self::EXTENSION_NAME, self::REPOSITORY_NAME, self::BRANCH_NAME);
    }

    public function testConstructorReturnsExtension()
    {
        $this->assertInstanceOf(Extension::class, $this->subject);
        $this->assertEquals(self::EXTENSION_NAME, $this->subject->getExtensionName());
        $this->assertEquals(self::REPOSITORY_NAME, $this->subject->getRepositoryName());
        $this->assertEquals(self::BRANCH_NAME, $this->subject->getBranchName());
        $this->assertEquals('dev-' . self::BRANCH_NAME, $this->subject->getPrefixedBranchName());
    }
}
