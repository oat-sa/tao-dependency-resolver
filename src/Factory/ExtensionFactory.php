<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Factory;

use OAT\DependencyResolver\Extension\Extension;

class ExtensionFactory
{
    private $extensionMap;

    public function __construct(array $extensionMap = [])
    {
        $this->extensionMap = $extensionMap;
    }

    public function create(string $extensionName, string $branch = Extension::DEFAULT_BRANCH): Extension
    {
        return new Extension($extensionName, $this->extensionMap[$extensionName], $branch);
    }
}
