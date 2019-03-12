<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\FileSystem\FileAccessException;
use OAT\DependencyResolver\Repository\Repository;
use OAT\DependencyResolver\Repository\RepositoryMapAccessor;

class ExtensionMapFactory
{
    /** @var RepositoryMapAccessor */
    private $repositoryMapAccessor;

    public function __construct(RepositoryMapAccessor $repositoryMapAccessor)
    {
        $this->repositoryMapAccessor = $repositoryMapAccessor;
    }

    /**
     * Creates an extension to repository name map.
     *
     * @return array
     * @throws FileAccessException When the repository table can not be read.
     */
    public function create(): array
    {
        $repositories = $this->repositoryMapAccessor->read();

        $extensionMap = [];
        foreach ($repositories as $repository) {
            /** @var Repository $repository */
            if ($repository->getExtensionName() !== '') {
                $extensionMap[$repository->getExtensionName()] = $repository->getOwner() . '/' . $repository->getName();
            }
        }

        return $extensionMap;
    }
}
