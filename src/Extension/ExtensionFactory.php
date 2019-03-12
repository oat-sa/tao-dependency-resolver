<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

class ExtensionFactory
{
    /** @var array */
    private $extensionMap = [];

    /**
     * ExtensionFactory constructor.
     * @param array $extensionMap
     */
    public function __construct(array $extensionMap)
    {
        $this->extensionMap = $extensionMap;
    }

    /**
     * @param string $extensionName
     * @param string $branch
     * @return Extension
     * @throws NotMappedException
     */
    public function create(string $extensionName, string $branch = Extension::DEFAULT_BRANCH): Extension
    {
        if (!isset($this->extensionMap[$extensionName])) {
            throw new NotMappedException(sprintf('Extension "%s" not found in map.', $extensionName));
        }

        $extensionMapItem = $this->extensionMap[$extensionName];
        $extension = new Extension($extensionName, $extensionMapItem['repository_name'], $extensionMapItem['composer_name'], $branch);

        return $extension;
    }
}
