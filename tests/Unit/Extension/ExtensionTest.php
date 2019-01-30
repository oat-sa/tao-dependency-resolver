<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Test\Unit\Extension;

use OAT\DependencyResolver\Extension\Extension;
use PHPUnit\Framework\TestCase;

class ExtensionTest extends TestCase
{
    public function testGettersPostConstruction()
    {
        $subject = new Extension('extensionName', 'repositoryName', 'branchName');

        $this->assertEquals('extensionName', $subject->getExtensionName());
        $this->assertEquals('repositoryName', $subject->getRepositoryName());
        $this->assertEquals('branchName', $subject->getBranchName());
        $this->assertEquals('dev-branchName', $subject->getPrefixedBranchName());

        $this->assertEquals(
            'https://raw.githubusercontent.com/repositoryName/branchName/composer.json',
            $subject->getRemoteComposerUrl()
        );
        $this->assertEquals(
            'https://raw.githubusercontent.com/repositoryName/branchName/manifest.php',
            $subject->getRemoteManifestUrl()
        );
    }
}
