<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;

class ExtensionFactory
{
    /** @var array */
    private $extensionMap = [];

    public function __construct(array $extensionMap)
    {
        $this->extensionMap = $extensionMap;
    }

    /**
     * @param string $extensionName
     * @param string $branch
     *
     * @return Extension
     * @throws NotMappedException
     */
    public function create(string $extensionName, string $branch = Extension::DEFAULT_BRANCH): Extension
    {
        if (!isset($this->extensionMap[$extensionName])) {
            $extensionName = $this->findExtensionNameFromRepositoryName($extensionName);
        }

        return new Extension($extensionName, $this->extensionMap[$extensionName], $branch);
    }

    /**
     * Tries to find an extension corresponding to the supposed repository name.
     *
     * @param string $extensionName
     *
     * @return string
     * @throws NotMappedException when the name can not be mapped to any extension.
     */
    private function findExtensionNameFromRepositoryName(string $extensionName)
    {
        // Extension names do not contain dashes so if there is no dash, we can't find the extension..
        if (strpos($extensionName, '-') === false) {
            throw new NotMappedException(sprintf('Extension "%s" not found in map.', $extensionName));
        }

        // Tries to find the repository name in the map values.
        $foundIndex = array_search($extensionName, $this->extensionMap);
        if ($foundIndex === false) {
            throw new NotMappedException(sprintf('Repository "%s" not found in map.', $extensionName));
        }

        return $foundIndex;
    }
}
