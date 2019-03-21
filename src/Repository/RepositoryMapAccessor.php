<?php declare(strict_types=1);

namespace OAT\DependencyResolver\Repository;

use OAT\DependencyResolver\Extension\Exception\NotMappedException;
use OAT\DependencyResolver\FileSystem\Exception\FileAccessException;
use OAT\DependencyResolver\FileSystem\FileAccessor;
use OAT\DependencyResolver\Repository\Entity\Repository;
use OAT\DependencyResolver\Repository\Entity\RepositoryBranch;
use OAT\DependencyResolver\Repository\Entity\RepositoryFile;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RepositoryMapAccessor
{
    const REPOSITORY_MAP_PATH = 'repository.map.path';

    /** @var FileAccessor */
    private $fileAccessor;

    /** @var string */
    private $extensionMapPath;

    /**
     * RepositoryMapAccessor constructor.
     *
     * @param ParameterBagInterface $parameterBag
     * @param FileAccessor          $fileAccessor
     */
    public function __construct(ParameterBagInterface $parameterBag, FileAccessor $fileAccessor)
    {
        $this->fileAccessor = $fileAccessor;

        if (! $parameterBag->has(self::REPOSITORY_MAP_PATH) || $parameterBag->get(self::REPOSITORY_MAP_PATH) === '') {
            throw new \LogicException('Parameter "' . self::REPOSITORY_MAP_PATH . '" missing or empty.');
        }
        $this->extensionMapPath = $parameterBag->get(self::REPOSITORY_MAP_PATH);
    }

    /**
     * Retrieves extension name from repository name.
     *
     * @param string $repositoryName
     *
     * @return string
     * @throws NotMappedException when the repository is not found in the map
     */
    public function findExtensionName(string $repositoryName): string
    {
        $repositoryList = $this->read();
        if (! isset($repositoryList[$repositoryName])) {
            throw new NotMappedException('Repository "' . $repositoryName . '" not found in map.');
        }

        /** @var Repository $repository */
        $repository = $repositoryList[$repositoryName];

        return $repository->getExtensionName();
    }

    /**
     * Reads extension map from configured file.
     *
     * @return array
     * @throws \LogicException when the extension map is not available or corrupted.
     */
    public function read(): array
    {
        try {
            $map = $this->fileAccessor->getContents($this->extensionMapPath);
        } catch (FileAccessException $exception) {
            throw new \LogicException('Extension map does not exist.');
        }

        $decodedMap = json_decode($map, true);
        if ($decodedMap === null) {
            throw new \LogicException('Extension map is not valid Json.');
        }

        // Builds Repository objects.
        foreach ($decodedMap as &$repository) {
            $repository = (new Repository())->constructFromArray($repository);
        }

        return $decodedMap;
    }

    /**
     * Writes extensionMap to configured file.
     *
     * @param array $map Extension maps extracted from updater.
     *
     * @return bool
     * @throws FileAccessException
     */
    public function write(array $map): bool
    {
        return $this->fileAccessor->setContents($this->extensionMapPath, json_encode($map, JSON_PRETTY_PRINT));
    }

    /**
     * Converts repository map to csv.
     *
     * @return array
     * @throws \LogicException
     */
    public function exportCsv(): array
    {
        $repositories = $this->read();

        // Sets titles.
        $csv = [
            implode(',', array_merge(
                Repository::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryBranch::CSV_TITLES,
                RepositoryFile::CSV_TITLES,
                RepositoryFile::CSV_TITLES
            )),
        ];

        // Builds Repository objects.
        /** @var Repository $repository */
        foreach ($repositories as $repository) {
            $csv[] = implode(',', $repository->toCsv());
        }

        return $csv;
    }
}
