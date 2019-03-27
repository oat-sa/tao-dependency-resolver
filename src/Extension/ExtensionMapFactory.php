<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Extension;

use OAT\DependencyResolver\Repository\Entity\Repository;
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
     * @throws \LogicException When the repository table can not be read.
     */
    public function create(): array
    {
        $extensionMap = [];
        foreach ($this->repositoryMapAccessor->read() as $repository) {
            /** @var Repository $repository */
            if ($repository->getExtensionName() !== '') {
                $extensionMap[$repository->getExtensionName()] = [
                    'repository_name' => $repository->getOwner() . '/' . $repository->getName(),
                    'composer_name' => $repository->getComposerName(),
                ];
            }
        }

        return $extensionMap;
    }
}
