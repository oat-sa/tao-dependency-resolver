<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;

class DependencyResolver
{
    /** @var ExtensionCollection */
    private $extensionCollection;

    /** @var RepositoryReaderInterface */
    private $repositoryReader;

    /** @var Parser */
    private $parser;

    /** @var ExtensionFactory */
    private $extensionFactory;

    public function __construct(
        RepositoryReaderInterface $repositoryReader,
        Parser $parser,
        ExtensionFactory $extensionFactory
    ) {
        $this->repositoryReader = $repositoryReader;
        $this->parser = $parser;
        $this->extensionFactory = $extensionFactory;
        $this->extensionCollection = new ExtensionCollection();
    }

    /**
     * @param Extension  $rootExtension
     * @param array $extensionBranchMap
     *
     * @return ExtensionCollection
     * @throws NotMappedException
     */
    public function resolve(Extension $rootExtension, array $extensionBranchMap): ExtensionCollection
    {
        // Adds the root extension so that it is installed along with the other ones.
        $this->extensionCollection->offsetSet($rootExtension->getExtensionName(), $rootExtension);

        // Extracts all the dependencies.
        $this->extractExtensionsRecursively($rootExtension, $extensionBranchMap);

        return $this->extensionCollection;
    }

    /**
     * @param Extension $rootExtension
     * @param array $extensionBranchMap
     *
     * @throws NotMappedException when the found extension is not mapped.
     */
    private function extractExtensionsRecursively(Extension $rootExtension, array $extensionBranchMap)
    {
        // Retrieves all required dependency names.
        [$owner, $repositoryName] = explode('/', $rootExtension->getRepositoryName());
        $manifestContents = $this->repositoryReader->getManifestContents(
            $owner,
            $repositoryName,
            $rootExtension->getBranchName()
        );

        if ($manifestContents === null) {
            return;
        }
        $dependencyNames = $this->parser->getDependencyNames($manifestContents);

        foreach ($dependencyNames as $extensionName) {
            if (! $this->extensionCollection->offsetExists($extensionName)) {
                // Finds the mapped branch.
                $branchName = $extensionBranchMap[$extensionName] ?? Extension::DEFAULT_BRANCH;

                // Adds dependency if not already in the collection.
                $extension = $this->extensionFactory->create($extensionName, $branchName);
                $this->extensionCollection->offsetSet($extensionName, $extension);

                // Looks for transitive dependencies.
                $this->extractExtensionsRecursively($extension, $extensionBranchMap);
            }
        }
    }
}
