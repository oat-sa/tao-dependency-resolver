<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Extension\Extension;
use OAT\DependencyResolver\Extension\ExtensionCollection;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Extension\NotMappedException;
use OAT\DependencyResolver\Repository\RepositoryReaderInterface;

class DependencyResolver
{
    /** @var array */
    private $extensionBranchMap = [];

    /** @var ExtensionCollection */
    private $extensionCollection;

    /** @var RepositoryReaderInterface */
    private $repositoryReader;

    /** @var Parser */
    private $parser;

    /** @var ExtensionFactory */
    private $extensionFactory;

    /**
     * DependencyResolver constructor.
     * @param RepositoryReaderInterface $repositoryReader
     * @param Parser $parser
     * @param ExtensionFactory $extensionFactory
     */
    public function __construct(
        RepositoryReaderInterface $repositoryReader,
        Parser $parser,
        ExtensionFactory $extensionFactory
    )
    {
        $this->repositoryReader = $repositoryReader;
        $this->parser = $parser;
        $this->extensionFactory = $extensionFactory;
        $this->extensionCollection = new ExtensionCollection();
    }

    /**
     * @param Extension $rootExtension
     * @param array|null $extensionBranchMap
     * @return ExtensionCollection
     * @throws NotMappedException
     */
    public function resolve(Extension $rootExtension, array $extensionBranchMap): ExtensionCollection
    {
        $this->extensionBranchMap = $extensionBranchMap;

        $this->extractExtensionsRecursively($rootExtension);

        return $this->extensionCollection;
    }

    /**
     * @param Extension $rootExtension
     * @throws NotMappedException when the found extension is not mapped.
     */
    private function extractExtensionsRecursively(Extension $rootExtension)
    {
        // Retrieves all required dependency names.
        [$owner, $repositoryName] = explode('/', $rootExtension->getRepositoryName());
        $manifestContents = $this->repositoryReader->getManifestContents($owner, $repositoryName, $rootExtension->getBranchName());

        if ($manifestContents === null) {
            return;
        }
        $dependencyNames = $this->parser->getDependencyNames($manifestContents);

        foreach ($dependencyNames as $extensionName) {
            if (!$this->extensionCollection->offsetExists($extensionName)) {
                // Finds the mapped branch.
                $branchName = $this->extensionBranchMap[$extensionName] ?? Extension::DEFAULT_BRANCH;

                // Adds dependency if not already in the collection.
                $extension = $this->extensionFactory->create($extensionName, $branchName);
                $this->extensionCollection->offsetSet($extensionName, $extension);

                // Looks for transitive dependencies.
                $this->extractExtensionsRecursively($extension);
            }
        }
    }
}
