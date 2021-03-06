<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;

class ExtensionFactory
{
    /** @var array */
    private $extensionMap;

    public function __construct(array $extensionMap = [])
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
            throw new NotMappedException(sprintf('Extension "%s" not found in map.', $extensionName));
        }

        return new Extension($extensionName, $this->extensionMap[$extensionName], $branch);
    }
}
