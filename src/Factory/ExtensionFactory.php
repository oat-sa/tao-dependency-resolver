<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Factory;

use OAT\DependencyResolver\Extension\Extension;

class ExtensionFactory
{
    public function create(
        string $extensionName,
        string $repositoryName,
        string $branch = Extension::DEFAULT_BRANCH
    ): Extension {
        return new Extension($extensionName, $repositoryName, $branch);
    }
}
