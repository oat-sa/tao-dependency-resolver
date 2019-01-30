<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Factory;

use Exception;
use OAT\DependencyResolver\Extension\Extension;

class ExtensionFactory
{
    private $extensionMap = [];

    public function __construct(array $extensionMap = [])
    {
        $this->extensionMap = $extensionMap;
    }

    /**
     * @throws Exception
     */
    public function create(string $extensionName, string $branch = Extension::DEFAULT_BRANCH): Extension
    {
        if (!isset($this->extensionMap[$extensionName])) {
            throw new Exception(sprintf("Extension '%s' not found in map.", $extensionName));
        }

        return new Extension($extensionName, $this->extensionMap[$extensionName], $branch);
    }
}
