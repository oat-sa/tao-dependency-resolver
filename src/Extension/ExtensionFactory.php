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
        if (! isset($this->extensionMap[$extensionName])) {
            // Extension names do not contain dashes so if there is no dash, we can't find the extension..
            if (strpos($extensionName, '-') === false) {
                throw new NotMappedException('Extension "' . $extensionName . '" not found in map.');
            }

            // Tries to find the repository name in the map values.
            $foundIndex = array_search($extensionName, array_column($this->extensionMap, 'repository_name'));
            if ($foundIndex === false) {
                throw new NotMappedException('Repository "' . $extensionName . '" not found in map.');
            }

            $extensionName = array_keys($this->extensionMap)[$foundIndex];
        }

        $extensionMapItem = $this->extensionMap[$extensionName];
        $extension = new Extension($extensionName, $extensionMapItem['repository_name'], $extensionMapItem['composer_name'], $branch);

        return $extension;
    }
}
