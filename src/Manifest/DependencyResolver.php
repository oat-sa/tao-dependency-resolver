<?php

declare(strict_types=1);

namespace OAT\DependencyResolver\Manifest;

use OAT\DependencyResolver\Extension\Entity\Extension;
use OAT\DependencyResolver\Extension\Entity\ExtensionCollection;
use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\Extension\ExtensionFactory;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryCollection;
use OAT\DependencyResolver\Repository\Interfaces\RepositoryReaderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class DependencyResolver implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
    }

    /**
     * @param Extension $rootExtension
     * @param array $extensionBranchMap
     * @param bool $repositoriesInfo
     * @return string
     * @throws NotMappedException
     */
    public function resolve(Extension $rootExtension, array $extensionBranchMap, bool $repositoriesInfo = false): string
    {
        // Adds the root extension so that it is installed along with the other ones.
        $extensionCollection = new ExtensionCollection();
        $extensionCollection->add($rootExtension);

        // Extracts all the dependencies.
        $extensionCollection = $this->extractExtensionsRecursively(
            $rootExtension,
            $extensionBranchMap,
            $extensionCollection
        );

        // Final data to be encoded.
        $compose = [];

        if ($repositoriesInfo !== false) {
            // Build repositories information.
            $this->logger->info('Retrieving repositories information.');
            $repositories = new RepositoryCollection();

            $repositoryCollection = new RepositoryCollection();
            /** @var Extension $extension */
            foreach ($extensionCollection as $extension) {
                // We do not go through GitHub API for repositories to avoid non critical extra requests.
                [$owner, $repositoryName] = explode('/', $extension->getRepositoryName());
                $repositoryCollection->add(new Repository(
                    false,
                    $owner,
                    $repositoryName,
                    false,
                    'master',
                    'unknown',
                    'unknown',
                    true,
                    []
                ));
            }

            $compose['repositories'] = $repositoryCollection->asArray();
        }

        $compose['require'] = $extensionCollection->asArray();

        $this->logger->info('Dependency Resolution process finished.');

        // Converts extension collection into a composer.json require.
        return json_encode(
            $compose,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * @param Extension $rootExtension
     * @param array $extensionBranchMap
     * @param ExtensionCollection $extensionCollection
     *
     * @return ExtensionCollection The amended collection
     * @throws NotMappedException when the found extension is not mapped.
     */
    private function extractExtensionsRecursively(
        Extension $rootExtension,
        array $extensionBranchMap,
        ExtensionCollection $extensionCollection
    ): ExtensionCollection {
        $this->logger->info('Resolving dependencies for repository "' . $rootExtension->getRepositoryName() . '".');

        // Retrieves all required dependency names from manifest.
        [$owner, $repositoryName] = explode('/', $rootExtension->getRepositoryName());
        $manifestContents = $this->repositoryReader->getManifestContents(
            $owner,
            $repositoryName,
            $rootExtension->getBranchName()
        );

        if ($manifestContents === null) {
            return $extensionCollection;
        }

        // Retrieves transitive dependencies.
        $dependencyNames = $this->parser->getDependencyNames($manifestContents);

        foreach ($dependencyNames as $extensionName) {
            if (! $extensionCollection->has($extensionName)) {
                // Finds the mapped branch.
                $branchName = $extensionBranchMap[$extensionName] ?? Extension::DEFAULT_BRANCH;

                // Adds dependency if not already in the collection.
                $extension = $this->extensionFactory->create($extensionName, $branchName);
                $extensionCollection->add($extension);

                // Looks for transitive dependencies.
                $this->extractExtensionsRecursively($extension, $extensionBranchMap, $extensionCollection);
            }
        }

        return $extensionCollection;
    }
}
